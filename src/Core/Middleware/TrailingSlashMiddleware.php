<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that normalizes URI paths by removing trailing slashes
 * (except for root '/') using HTTP 301/308 redirects.
 */
class TrailingSlashMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly int $statusCode = 301
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $pathInfo = $request->getPathInfo();

        if ($pathInfo !== '/' && str_ends_with($pathInfo, '/')) {
            $trimmedPath = rtrim($pathInfo, '/');
            $qs = $request->getQueryString();
            $targetUrl = $trimmedPath . ($qs !== null && $qs !== '' ? '?' . $qs : '');

            return new RedirectResponse($targetUrl, $this->statusCode);
        }

        return $next($request);
    }
}
