<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles Cross-Origin Resource Sharing (CORS) headers and preflight OPTIONS requests.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $allowedOrigins Allowed origins or ['*']
     * @param list<string> $allowedMethods Allowed HTTP methods
     * @param list<string> $allowedHeaders Allowed HTTP request headers
     * @param list<string> $exposedHeaders Headers exposed to the client
     * @param bool $allowCredentials Whether Access-Control-Allow-Credentials is true
     * @param int $maxAge Preflight cache duration in seconds
     */
    public function __construct(
        private readonly array $allowedOrigins = ['*'],
        private readonly array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        private readonly array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
        private readonly array $exposedHeaders = [],
        private readonly bool $allowCredentials = false,
        private readonly int $maxAge = 86400
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $origin = $request->headers->get('Origin');

        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS') && $request->headers->has('Access-Control-Request-Method')) {
            $response = new Response('', Response::HTTP_NO_CONTENT);
            $this->applyCorsHeaders($response, $origin);
            return $response;
        }

        /** @var Response $response */
        $response = $next($request);

        if ($origin !== null) {
            $this->applyCorsHeaders($response, $origin);
        }

        return $response;
    }

    private function applyCorsHeaders(Response $response, ?string $origin): void
    {
        if ($origin === null) {
            return;
        }

        $allowOrigin = $this->resolveAllowedOrigin($origin);
        if ($allowOrigin === null) {
            return;
        }

        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        $response->headers->set('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
        $response->headers->set('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));

        if ($this->allowCredentials) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($this->exposedHeaders)) {
            $response->headers->set('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
        }

        if ($this->maxAge > 0) {
            $response->headers->set('Access-Control-Max-Age', (string) $this->maxAge);
        }

        $response->headers->set('Vary', 'Origin', false);
    }

    private function resolveAllowedOrigin(string $origin): ?string
    {
        if (in_array('*', $this->allowedOrigins, true)) {
            return $this->allowCredentials ? $origin : '*';
        }

        if (in_array($origin, $this->allowedOrigins, true)) {
            return $origin;
        }

        return null;
    }
}
