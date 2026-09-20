<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware ensuring every HTTP request carries a unique Request ID (Correlation ID).
 * If the incoming request has X-Request-ID, it is preserved; otherwise a new UUID is generated.
 * The ID is added to request attributes and returned in response headers.
 */
class RequestIdMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE_NAME = 'request_id';
    public const HEADER_NAME = 'X-Request-ID';

    public function __construct(
        private readonly string $headerName = self::HEADER_NAME,
        private readonly string $attributeName = self::ATTRIBUTE_NAME
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $incomingId = (string) $request->headers->get($this->headerName, '');
        $requestId = $incomingId !== '' ? $incomingId : $this->generateUuid();

        $request->attributes->set($this->attributeName, $requestId);

        $response = $next($request);

        if (!$response->headers->has($this->headerName)) {
            $response->headers->set($this->headerName, $requestId);
        }

        return $response;
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // UUID v4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // RFC 4122 variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
