<?php

declare(strict_types=1);

namespace Nqphp\Core\Controller;

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
     * Return an HTML response. By default this just returns the body
     * verbatim. Override `render` in subclasses to integrate a
     * templating engine.
     */
    protected function render(string $body, int $status = 200, array $headers = []): Response
    {
        return new Response($body, $status, $headers);
    }

    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}
