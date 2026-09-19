<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds standard security and privacy headers to outgoing HTTP responses.
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string, string> $customHeaders Additional or overriding headers
     */
    public function __construct(
        private readonly array $customHeaders = [],
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $defaultHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()',
        ];

        $headers = array_merge($defaultHeaders, $this->customHeaders);

        foreach ($headers as $name => $value) {
            if (!$response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
