<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\RateLimit;

/**
 * Null-object implementation of RateLimiterInterface that always permits requests.
 * Useful for testing, development, and disabling rate limiting per environment.
 */
class NullRateLimiter implements RateLimiterInterface
{
    public function hit(string $key, int $maxAttempts, int $decaySeconds): array
    {
        return [
            'allowed' => true,
            'remaining' => $maxAttempts,
            'retry_after' => 0,
            'reset_at' => time() + $decaySeconds,
        ];
    }

    public function reset(string $key): void
    {
        // No-op
    }
}
