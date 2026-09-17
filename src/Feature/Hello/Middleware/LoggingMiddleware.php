<?php

declare(strict_types=1);

namespace Nqphp\Feature\Hello\Middleware;

use Nqphp\Core\Attribute\Middleware;
use Symfony\Component\HttpFoundation\Request;

/**
 * Demo middleware: logs every request method + path. Order 100 (default).
 * Demonstrates the #[Middleware] attribute lifecycle.
 *
 * Real applications would have CSRF / CORS / Auth / Rate-limit middlewares
 * with lower orders so they run first.
 */
#[Middleware(name: 'hello:logging', order: 100)]
final class LoggingMiddleware
{
    public function handle(Request $request): ?\Symfony\Component\HttpFoundation\Response
    {
        \error_log(sprintf('[hello:logging] %s %s', $request->getMethod(), $request->getPathInfo()));
        return null;  // continue
    }
}
