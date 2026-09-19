<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\RateLimit;

/**
 * Composite rate limiter checking multiple RateLimiterInterface instances.
 * Fails fast if any constituent limiter denies the request.
 */
final class CompositeRateLimiter implements RateLimiterInterface
{
    /**
     * @param list<RateLimiterInterface> $limiters
     */
    public function __construct(private readonly array $limiters)
    {
    }

    public function hit(string $key, int $maxAttempts, int $decaySeconds): array
    {
        $minRemaining = $maxAttempts;
        $maxRetryAfter = 0;
        $maxResetAt = time() + $decaySeconds;
        $allowed = true;

        foreach ($this->limiters as $limiter) {
            $result = $limiter->hit($key, $maxAttempts, $decaySeconds);

            if (!$result['allowed']) {
                $allowed = false;
                $maxRetryAfter = max($maxRetryAfter, $result['retry_after']);
            }

            $minRemaining = min($minRemaining, $result['remaining']);
            $maxResetAt = max($maxResetAt, $result['reset_at']);
        }

        return [
            'allowed' => $allowed,
            'remaining' => $allowed ? $minRemaining : 0,
            'retry_after' => $allowed ? 0 : max(1, $maxRetryAfter),
            'reset_at' => $maxResetAt,
        ];
    }

    public function reset(string $key): void
    {
        foreach ($this->limiters as $limiter) {
            $limiter->reset($key);
        }
    }
}
