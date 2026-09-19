<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\RateLimit;

use Nqphp\Core\Cache\CacheInterface;

/**
 * Distributed / persistent rate limiter backed by any CacheInterface implementation.
 */
class CacheRateLimiter implements RateLimiterInterface
{
    private const KEY_PREFIX = 'rate_limit:';

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function hit(string $key, int $maxAttempts, int $decaySeconds): array
    {
        $cacheKey = self::KEY_PREFIX . $key;
        $now = time();

        /** @var array<int> $timestamps */
        $timestamps = $this->cache->get($cacheKey, []);
        if (!is_array($timestamps)) {
            $timestamps = [];
        }

        $cutoff = $now - $decaySeconds;
        $timestamps = array_values(array_filter(
            $timestamps,
            static fn (int $ts): bool => $ts > $cutoff
        ));

        if (count($timestamps) >= $maxAttempts) {
            $oldestTimestamp = $timestamps[0] ?? $now;
            $retryAfter = max(1, ($oldestTimestamp + $decaySeconds) - $now);
            $resetAt = $now + $retryAfter;

            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $retryAfter,
                'reset_at' => $resetAt,
            ];
        }

        $timestamps[] = $now;
        $this->cache->set($cacheKey, $timestamps, $decaySeconds * 2);

        $remaining = max(0, $maxAttempts - count($timestamps));
        $resetAt = $now + $decaySeconds;

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'retry_after' => 0,
            'reset_at' => $resetAt,
        ];
    }

    /**
     * @inheritDoc
     */
    public function reset(string $key): void
    {
        $this->cache->delete(self::KEY_PREFIX . $key);
    }
}
