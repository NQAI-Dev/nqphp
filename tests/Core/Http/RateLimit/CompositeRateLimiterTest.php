<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\RateLimit;

use Nqphp\Core\Http\RateLimit\CompositeRateLimiter;
use Nqphp\Core\Http\RateLimit\InMemoryRateLimiter;
use PHPUnit\Framework\TestCase;

class CompositeRateLimiterTest extends TestCase
{
    public function testAllowsWhenAllLimitersAllow(): void
    {
        $limiter1 = new InMemoryRateLimiter();
        $limiter2 = new InMemoryRateLimiter();
        $composite = new CompositeRateLimiter([$limiter1, $limiter2]);

        $status = $composite->hit('user-1', 5, 60);

        $this->assertTrue($status['allowed']);
        $this->assertSame(4, $status['remaining']);
        $this->assertSame(0, $status['retry_after']);
    }

    public function testDeniesWhenAnyConstituentDenies(): void
    {
        $limiter1 = new InMemoryRateLimiter();
        $limiter2 = new InMemoryRateLimiter();

        // Exhaust limiter2
        for ($i = 0; $i < 2; $i++) {
            $limiter2->hit('user-2', 2, 60);
        }

        $composite = new CompositeRateLimiter([$limiter1, $limiter2]);
        $status = $composite->hit('user-2', 2, 60);

        $this->assertFalse($status['allowed']);
        $this->assertSame(0, $status['remaining']);
        $this->assertGreaterThan(0, $status['retry_after']);
    }

    public function testResetsAllLimiters(): void
    {
        $limiter1 = new InMemoryRateLimiter();
        $limiter2 = new InMemoryRateLimiter();

        $composite = new CompositeRateLimiter([$limiter1, $limiter2]);

        // Consume hits
        $composite->hit('user-3', 2, 60);
        $composite->hit('user-3', 2, 60);

        $statusBlocked = $composite->hit('user-3', 2, 60);
        $this->assertFalse($statusBlocked['allowed']);

        $composite->reset('user-3');

        $statusAfterReset = $composite->hit('user-3', 2, 60);
        $this->assertTrue($statusAfterReset['allowed']);
        $this->assertSame(1, $statusAfterReset['remaining']);
    }
}
