<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\RateLimit;

use Nqphp\Core\Http\RateLimit\NullRateLimiter;
use Nqphp\Core\Http\RateLimit\RateLimiterInterface;
use PHPUnit\Framework\TestCase;

class NullRateLimiterTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $limiter = new NullRateLimiter();
        $this->assertInstanceOf(RateLimiterInterface::class, $limiter);
    }

    public function testHitAlwaysAllowed(): void
    {
        $limiter = new NullRateLimiter();

        for ($i = 0; $i < 5; $i++) {
            $result = $limiter->hit('user-1', 3, 60);

            $this->assertTrue($result['allowed']);
            $this->assertSame(3, $result['remaining']);
            $this->assertSame(0, $result['retry_after']);
            $this->assertGreaterThanOrEqual(time() + 59, $result['reset_at']);
        }
    }

    public function testResetDoesNotThrow(): void
    {
        $limiter = new NullRateLimiter();
        $limiter->reset('user-1');
        $this->assertTrue(true);
    }
}
