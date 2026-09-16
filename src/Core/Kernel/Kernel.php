<?php

declare(strict_types=1);

namespace Nqphp\Core\Kernel;

use Nqphp\Core\Routing\Router;
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
 * Phase 2+ (planned): middleware pipeline, container-managed services,
 *   per-feature config autoloading.
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

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->router = new Router([
            $projectDir . '/src/Feature',
        ]);
    }

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response
    {
        $routes = $this->router->discover();
        $context = (new RequestContext())->fromRequest($request);
        $matcher = new UrlMatcher($routes, $context);

        try {
            $params = $matcher->match($request->getPathInfo());
        } catch (ResourceNotFoundException $e) {
            return new Response('Not Found', 404);
        }

        [$class, $method] = explode('::', $params['_controller'], 2);
        if (!class_exists($class)) {
            return new Response(sprintf('Class %s not found', $class), 500);
        }

        $instance = new $class();
        if (!method_exists($instance, $method)) {
            return new Response(sprintf('Method %s::%s not found', $class, $method), 500);
        }

        // Strip our private _controller / _method entries before invoking.
        unset($params['_controller'], $params['_method']);

        $reflection = new \ReflectionMethod($instance, $method);
        $args = $this->resolveArgs($reflection, $params, $request);
        $result = $reflection->invokeArgs($instance, $args);

        if ($result instanceof Response) {
            return $result;
        }
        return new Response((string) ($result ?? ''), 200, ['content-type' => 'text/plain']);
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
}
