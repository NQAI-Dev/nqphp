<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Nqphp\Core\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Basic rate limiter using CacheInterface.
 *
 * Limits requests per client IP within a rolling time window.
 * If the limit is exceeded, returns a 429 Too Many Requests response.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private CacheInterface $cache;
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(CacheInterface $cache, int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->cache = $cache;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function process(Request $request, callable $next): Response
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $key = 'rate_limit:' . $ip;

        $current = (int) $this->cache->get($key, 0);

        if ($current >= $this->maxRequests) {
            return new Response('Too Many Requests', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $this->cache->set($key, $current + 1, $this->windowSeconds);

        return $next($request);
    }
}
