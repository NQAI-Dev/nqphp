<?php

declare(strict_types=1);

namespace Nqphp\Core\Kernel;

use Nqphp\Core\Js\JsModuleServer;
use Nqphp\Core\Routing\Router;
use Nqphp\Core\Security\CsrfTokenManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

/**
 * Minimal HTTP kernel.
 *
 * Phase 1: route resolution + dispatch to controller method.
 * Phase 2: framework-internal `/_nqphp/...` namespace (JS modules,
 *   CSRF cookie + enforcement), per-feature config autoloading.
 *
 * The kernel is intentionally tiny — almost all of the heavy lifting
 * delegates to Symfony components (Routing, HttpFoundation, HttpKernel).
 * The kernel's job is wiring.
 */
final class Kernel implements HttpKernelInterface
{
    /** @var string */
    private $projectDir;

    /** @var \Nqphp\Core\Routing\Router */
    private $router;

    /** @var \Nqphp\Core\Middleware\MiddlewareDiscoverer */
    private readonly MiddlewareDiscoverer $middlewareDiscoverer;

    /** @var \Nqphp\Core\Config\FeatureConfig */
    private readonly FeatureConfig $featureConfig;

    private ?CsrfTokenManager $csrf;
    private ?JsModuleServer $js;

    public function __construct(
        string $projectDir,
        ?CsrfTokenManager $csrf = null,
        ?JsModuleServer $js = null,
    ) {
        $this->projectDir = $projectDir;
        $this->router = new Router([
            $projectDir . '/src/Feature',
        ]);
        $this->middlewareDiscoverer = new MiddlewareDiscoverer([
            $projectDir . '/src/Feature',
            $projectDir . '/src/Core',
        ]);
        $this->featureConfig = new FeatureConfig([
            $projectDir . '/src/Feature',
        ]);
        $this->csrf = $csrf;
        $this->js = $js;
    }

    /** Public accessor for tests + future introspection commands
     *  (e.g. `bin/console middleware:list` mirroring `routes:list`). */
    public function middlewareDiscoverer(): MiddlewareDiscoverer
    {
        return $this->middlewareDiscoverer;
    }

    /** Look up a per-feature config value. Returns $default if the
     *  feature has no config or no value for $key. */
    public function config(string $feature, string $key, mixed $default = null): mixed
    {
        return $this->featureConfig->load()->get($feature, $key, $default);
    }

    /** Raw config map for a feature (test/debug helper). */
    public function featureConfig(): FeatureConfig
    {
        return $this->featureConfig;
    }

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response
    {
        // Framework-internal namespace: JS modules + future framework
        // endpoints. Resolved before user routes so feature code never
        // shadows the runtime.
        if ($this->js !== null && str_starts_with($request->getPathInfo(), JsModuleServer::urlPrefix())) {
            $relative = substr($request->getPathInfo(), strlen(JsModuleServer::urlPrefix()));
            $response = $this->js->serve($relative);
            if ($response !== null) {
                return $this->withCsrfCookie($request, $response);
            }
            return new Response('Not Found', 404);
        }

        // Enforce CSRF on state-changing user requests before dispatch.
        if ($this->csrf !== null && !$this->csrf->isValid($request)) {
            return $this->withCsrfCookie(
                $request,
                new Response('CSRF token missing or invalid', 403, ['content-type' => 'text/plain']),
            );
        }

        // Run discovered middlewares in `order` ascending. Each
        // middleware either returns a Response (terminal — short-
        // circuits everything below) or null (continue). CSRF + auth
        // belong at low numbers (so they can deny), logging /
        // headers / metrics at high numbers (so they run last).
        $middlewares = $this->middlewareDiscoverer->discover()->all();
        foreach ($middlewares as $mw) {
            [$instance, $method] = $mw['callable'];
            $result = $instance->$method($request);
            if ($result instanceof Response) {
                return $this->withCsrfCookie($request, $result);
            }
            // null → continue to the next middleware.
        }

        $routes = $this->router->discover();
        $context = (new RequestContext())->fromRequest($request);
        $matcher = new UrlMatcher($routes, $context);

        try {
            $params = $matcher->match($request->getPathInfo());
        } catch (ResourceNotFoundException $e) {
            return $this->withCsrfCookie($request, new Response('Not Found', 404));
        }

        [$class, $method] = explode('::', $params['_controller'], 2);
        if (!class_exists($class)) {
            return $this->withCsrfCookie($request, new Response(sprintf('Class %s not found', $class), 500));
        }

        $instance = new $class();
        if (!method_exists($instance, $method)) {
            return $this->withCsrfCookie($request, new Response(sprintf('Method %s::%s not found', $class, $method), 500));
        }

        // Strip our private _controller / _method entries before invoking.
        unset($params['_controller'], $params['_method']);

        $reflection = new \ReflectionMethod($instance, $method);
        $args = $this->resolveArgs($reflection, $params, $request);
        $result = $reflection->invokeArgs($instance, $args);

        if ($result instanceof Response) {
            return $this->withCsrfCookie($request, $result);
        }
        return $this->withCsrfCookie($request, new Response((string) ($result ?? ''), 200, ['content-type' => 'text/plain']));
    }

    /**
     * Attach the CSRF cookie to a response if the framework has a
     * CsrfTokenManager wired up. Idempotent: if the cookie is already
     * present we don't issue a new one — the existing token stays put.
     */
    private function withCsrfCookie(Request $request, Response $response): Response
    {
        if ($this->csrf === null) {
            return $response;
        }
        $existing = $request->cookies->get(CsrfTokenManager::COOKIE_NAME);
        if (is_string($existing) && $existing !== '') {
            return $response;
        }
        $response->headers->setCookie($this->csrf->buildCookie($request));
        return $response;
    }

    /**
     * Resolve route parameters + Request into method argument order.
     *
     * Phase 1: positional binding of route placeholders. The Request
     * is injected if the parameter is typed Request.
     *
     * @param \ReflectionMethod $method
     * @param array<string,mixed> $params
     */
    private function resolveArgs(\ReflectionMethod $method, array $params, Request $request): array
    {
        $args = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (isset($params[$name])) {
                $args[] = $params[$name];
                continue;
            }
            if ($param->getType() && $param->getType()->getName() === Request::class) {
                $args[] = $request;
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }
            $args[] = null;
        }
        return $args;
    }

    public function getRouteCollection(): \Symfony\Component\Routing\RouteCollection
    {
        return $this->router->discover();
    }

    public function getCsrf(): ?CsrfTokenManager
    {
        return $this->csrf;
    }

    public function getJs(): ?JsModuleServer
    {
        return $this->js;
    }
}
