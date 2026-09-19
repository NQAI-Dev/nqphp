<?php

declare(strict_types=1);

namespace Nqphp\Core\Http;

use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Converts exceptions to safe, content-negotiated HTTP responses.
 */
final readonly class ErrorResponseFormatter
{
    public function __construct(private bool $debug = false)
    {
    }

    public function format(Request $request, Throwable $exception): Response
    {
        $status = $exception instanceof HttpException ? $exception->getStatusCode() : 500;
        if ($status < 400 || $status > 599) {
            $status = 500;
        }

        $title = Response::$statusTexts[$status] ?? 'Error';
        $detail = $exception instanceof HttpException || $this->debug
            ? ($exception->getMessage() !== '' ? $exception->getMessage() : $title)
            : 'Internal Server Error';

        $response = match ($this->preferredType($request)) {
            'json' => $this->json($request, $status, $title, $detail, $exception),
            'html' => $this->html($status, $title, $detail),
            default => new Response($detail, $status, ['Content-Type' => 'text/plain; charset=UTF-8']),
        };

        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Vary', 'Accept');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function preferredType(Request $request): string
    {
        foreach ($request->getAcceptableContentTypes() as $type) {
            if ($type === 'application/problem+json' || $type === 'application/json' || str_ends_with($type, '+json')) {
                return 'json';
            }
            if ($type === 'text/html' || $type === 'application/xhtml+xml') {
                return 'html';
            }
            if ($type === 'text/plain' || $type === '*/*') {
                return 'text';
            }
        }

        return 'text';
    }

    private function json(
        Request $request,
        int $status,
        string $title,
        string $detail,
        Throwable $exception,
    ): JsonResponse {
        $problem = [
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $request->getRequestUri(),
        ];

        if ($exception instanceof ValidationException && $exception->getErrors() !== []) {
            $problem['errors'] = $exception->getErrors();
        }

        if ($this->debug && !$exception instanceof HttpException) {
            $problem['exception'] = $exception::class;
        }

        return new JsonResponse($problem, $status, ['Content-Type' => 'application/problem+json']);
    }

    private function html(int $status, string $title, string $detail): Response
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeDetail = htmlspecialchars($detail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $body = "<!doctype html>\n<html lang=\"en\"><head><meta charset=\"utf-8\"><title>{$status} {$safeTitle}</title></head>"
            . "<body><h1>{$status} {$safeTitle}</h1><p>{$safeDetail}</p></body></html>";

        return new Response($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
