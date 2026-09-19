<?php

declare(strict_types=1);

namespace Nqphp\Core\Controller;

use Nqphp\Core\Validation\ValidationException;
use Nqphp\Core\Validation\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for controllers. Provides render/redirect/json helpers.
 *
 * Subclasses override these to integrate templating; the framework
 * itself doesn't bundle a templating engine (use Twig, Plates, plain
 * PHP, or whatever fits the feature).
 */
abstract class AbstractController
{
    /**
     * Construct with a Kernel reference so subclasses can call
     * `$this->kernel->entityManager()`, `$this->kernel->config(...)`,
     * `$this->kernel->url(...)`, etc. without needing a separate
     * service container. The Kernel dispatches by passing itself
     * during controller instantiation via the controller-injection
     * helper (see Kernel::dispatch()).
     */
    /**
     * Kernel reference, injected via reflection by the framework after
     * construction. Formally declared (not a dynamic property) so
     * ReflectionClass::hasProperty('kernel') returns true and the
     * Kernel can setValue() on it for any AbstractController subclass.
     */
    protected ?\Nqphp\Core\Kernel\Kernel $kernel = null;
    protected ?\Nqphp\Core\View\RouteAssetManager $assetManager = null;

    public function __construct(?\Nqphp\Core\Kernel\Kernel $kernel = null)
    {
        // Kernel is optional at construction time. The framework
        // dispatches by setting the `kernel` property via reflection
        // right after `new $class()` for AbstractController subclasses,
        // so the property is non-null by the time any route handler
        // runs. Plain non-AbstractController controllers don't need
        // a Kernel reference and can construct without args.
        $this->kernel = $kernel;
    }

    /**
     * Return an HTML response. By default this just returns the body
     * verbatim. Override `render` in subclasses to integrate a
     * templating engine.
     */
    protected function renderString(string $body, int $status = 200, array $headers = []): Response
    {
        return new Response($body, $status, $headers);
    }

    /**
     * Return an HTML response from a native PHP view template.
     * Automatically registers and attaches scoped CSS and route JS if present.
     */
    protected function render(string $viewPath, array $data = [], int $status = 200, array $headers = []): Response
    {
        $renderer = new \Nqphp\Core\View\ViewRenderer(dirname($viewPath));
        $baseName = basename($viewPath);
        $body = $renderer->render($baseName, $data);
        $response = new Response($body, $status, $headers);

        if ($this->kernel !== null) {
            $viewDir = dirname($viewPath);
            $pureName = pathinfo($baseName, PATHINFO_FILENAME);
            $scopedCssFile = $viewDir . '/' . $pureName . '.scoped.css';
            $routeJsFile = $viewDir . '/' . $pureName . '.route.js';

            $assetManager = new \Nqphp\Core\View\RouteAssetManager($this->kernel->getProjectDir());
            $cssUrl = null;
            $jsUrl = null;

            if (file_exists($scopedCssFile)) {
                $scopedInfo = $assetManager->registerScopedCss($scopedCssFile, $pureName);
                $cssUrl = $scopedInfo['assetUrl'];
            }

            if (file_exists($routeJsFile)) {
                // Feature route JS
                $relJs = substr($routeJsFile, strlen($this->kernel->getProjectDir()));
                $jsUrl = '/_nqphp/asset' . $relJs;
            }

            $assetManager->attachToResponse($response, $cssUrl, $jsUrl);
        }

        return $response;
    }

    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Session manager helper.
     */
    protected function session(): \Nqphp\Core\Session\SessionInterface
    {
        if ($this->kernel === null) {
            throw new \RuntimeException(
                'Cannot access session outside a kernel dispatch: kernel reference is not set.'
            );
        }
        return $this->kernel->session();
    }

    /**
     * Add a flash message to the session.
     */
    protected function addFlash(string $key, mixed $message): void
    {
        $this->session()->addFlash($key, $message);
    }

    /**
     * Get flash messages for the given key and clear them from the session.
     *
     * @return array<array-key, mixed>
     */
    protected function getFlash(string $key, array $default = []): array
    {
        return $this->session()->getFlash($key, $default);
    }

    /**
     * Hydrate a `#[Input]` DTO from the current request and validate
     * it against its `#[Assert]` attributes. On failure a 422
     * ValidationException propagates to the kernel error boundary,
     * which renders a problem+json response with the per-field
     * `errors` map. On success the hydrated DTO instance is returned.
     *
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return T
     */
    protected function validate(string $dtoClass): object
    {
        if ($this->kernel === null) {
            throw new \RuntimeException(
                'Cannot validate input outside a kernel dispatch: kernel reference is not set.'
            );
        }

        $dto = $this->kernel->input($dtoClass);
        $errors = (new Validator())->validate($dto);
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        return $dto;
    }
    /**
     * Helper to access the framework EntityManager instance.
     */
    protected function em(): \Nqphp\Core\Entity\EntityManager
    {
        return $this->kernel->entityManager();
    }
    /**
     * Redirect to a named route by generating its URL from the kernel router.
     *
     * Usage:
     *   return $this->redirectToRoute('blog:post:show', ['slug' => 'hello-world']);
     *   return $this->redirectToRoute('admin:dashboard', status: 301);
     */
    protected function redirectToRoute(string $routeName, array $params = [], int $status = 302): RedirectResponse
    {
        if ($this->kernel === null) {
            throw new \RuntimeException(
                'Cannot generate URL outside a kernel dispatch: kernel reference is not set.'
            );
        }
        return $this->redirect($this->kernel->url($routeName, $params), $status);
    }
    /**
     * Get the authenticated user from the security context, or null if anonymous.
     */
    protected function getUser(): ?\Nqphp\Core\Security\UserInterface
    {
        if ($this->kernel === null) {
            throw new \RuntimeException('Cannot access user outside a kernel dispatch: kernel reference is not set.');
        }
        return $this->kernel->security()->getUser();
    }

    /**
     * Check if the current user has the given role/permission.
     */
    protected function isGranted(string $role): bool
    {
        if ($this->kernel === null) {
            throw new \RuntimeException('Cannot check permissions outside a kernel dispatch: kernel reference is not set.');
        }
        return $this->kernel->security()->isGranted($role);
    }

    /**
     * Deny access unless the given role is granted, throwing 403 HttpException.
     */
    protected function denyAccessUnlessGranted(string $role, string $message = 'Access Denied.'): void
    {
        if (!$this->isGranted($role)) {
            throw new \Nqphp\Core\Exception\HttpException(403, $message);
        }
    }

    /**
     * Return a file download response (Content-Disposition: attachment).
     */
    protected function fileDownload(string $filePath, ?string $fileName = null, array $headers = []): \Nqphp\Core\Http\FileResponse
    {
        return \Nqphp\Core\Http\FileResponse::download($filePath, $fileName, $headers);
    }

    /**
     * Return an inline file response (Content-Disposition: inline).
     */
    protected function fileInline(string $filePath, ?string $fileName = null, array $headers = []): \Nqphp\Core\Http\FileResponse
    {
        return \Nqphp\Core\Http\FileResponse::inline($filePath, $fileName, $headers);
    }
}
