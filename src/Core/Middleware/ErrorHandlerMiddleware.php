<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nqphp\Core\Exception\HttpException;
use Throwable;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (HttpException $e) {
            return $this->createResponse($request, $e->getMessage(), $e->getStatusCode());
        } catch (Throwable $e) {
            // В production режиме детали ошибки лучше скрывать, но пока оставим базовый fallback
            return $this->createResponse($request, 'Internal Server Error', 500);
        }
    }

    private function createResponse(Request $request, string $message, int $status): Response
    {
        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            return new JsonResponse(['error' => ['message' => $message, 'code' => $status]], $status);
        }
        
        return new Response($message, $status);
    }
}
