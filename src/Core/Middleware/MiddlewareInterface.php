<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware handler.
     */
    public function process(Request $request, callable $next): Response;
}
