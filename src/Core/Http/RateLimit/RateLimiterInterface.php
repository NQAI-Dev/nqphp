<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\RateLimit;

interface RateLimiterInterface
{
    /**
     * Attempt an action for a given key.
     *
     * @param string $key Unique rate-limit identifier (e.g. client IP, user ID)
     * @param int $maxAttempts Maximum allowed hits within decay window
     * @param int $decaySeconds Sliding window interval in seconds
     * @return array{allowed: bool, remaining: int, retry_after: int, reset_at: int}
     */
    public function hit(string $key, int $maxAttempts, int $decaySeconds): array;

    /**
     * Clear rate limit history for a key.
     */
    public function reset(string $key): void;
}
