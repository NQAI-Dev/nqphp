<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Nqphp\Core\Http\RateLimit\RateLimiterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RateLimiterMiddleware implements MiddlewareInterface
{
    private RateLimiterInterface $limiter;
    private int $maxAttempts;
    private int $decaySeconds;

    public function __construct(RateLimiterInterface $limiter, int $maxAttempts = 60, int $decaySeconds = 60)
    {
        $this->limiter = $limiter;
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    public function process(Request $request, callable $next): Response
    {
        $clientIp = $request->getClientIp() ?: '127.0.0.1';
        $key = 'rate_limit:' . $clientIp;

        $status = $this->limiter->hit($key, $this->maxAttempts, $this->decaySeconds);

        if (!$status['allowed']) {
            $data = [
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Too Many Requests',
                'status' => Response::HTTP_TOO_MANY_REQUESTS,
                'detail' => sprintf('Rate limit exceeded. Try again in %d seconds.', $status['retry_after']),
            ];

            return new JsonResponse($data, Response::HTTP_TOO_MANY_REQUESTS, [
                'Content-Type' => 'application/problem+json',
                'Retry-After' => (string) $status['retry_after'],
                'X-RateLimit-Limit' => (string) $this->maxAttempts,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) $status['reset_at'],
            ]);
        }

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $status['remaining']);
        $response->headers->set('X-RateLimit-Reset', (string) $status['reset_at']);

        return $response;
    }
}
