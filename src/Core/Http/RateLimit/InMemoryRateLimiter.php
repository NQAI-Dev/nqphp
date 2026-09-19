<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\RateLimit;

final class InMemoryRateLimiter implements RateLimiterInterface
{
    /**
     * @var array<string, list<int>> key -> timestamps
     */
    private array $hits = [];

    public function hit(string $key, int $maxAttempts, int $decaySeconds): array
    {
        $now = time();
        $cutoff = $now - $decaySeconds;

        if (!isset($this->hits[$key])) {
            $this->hits[$key] = [];
        }

        // Filter out expired hits
        $this->hits[$key] = array_values(array_filter($this->hits[$key], fn (int $ts) => $ts > $cutoff));

        $count = count($this->hits[$key]);

        if ($count >= $maxAttempts) {
            $oldestHit = $this->hits[$key][0] ?? $now;
            $retryAfter = max(1, ($oldestHit + $decaySeconds) - $now);
            $resetAt = $oldestHit + $decaySeconds;

            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $retryAfter,
                'reset_at' => $resetAt,
            ];
        }

        $this->hits[$key][] = $now;
        $remaining = max(0, $maxAttempts - count($this->hits[$key]));
        $resetAt = $now + $decaySeconds;

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'retry_after' => 0,
            'reset_at' => $resetAt,
        ];
    }

    public function reset(string $key): void
    {
        unset($this->hits[$key]);
    }
}
