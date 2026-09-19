<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Nqphp\Core\Http\ErrorResponseFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(private ErrorResponseFormatter $formatter = new ErrorResponseFormatter())
    {
    }

    public function process(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $exception) {
            return $this->formatter->format($request, $exception);
        }
    }
}
