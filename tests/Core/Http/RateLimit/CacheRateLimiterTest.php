<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\RateLimit;

use Nqphp\Core\Cache\ArrayCache;
use Nqphp\Core\Http\RateLimit\CacheRateLimiter;
use PHPUnit\Framework\TestCase;

class CacheRateLimiterTest extends TestCase
{
    public function testAllowsAttemptsWithinLimit(): void
    {
        $cache = new ArrayCache();
        $limiter = new CacheRateLimiter($cache);

        $res1 = $limiter->hit('user:10', 3, 60);
        $this->assertTrue($res1['allowed']);
        $this->assertSame(2, $res1['remaining']);
        $this->assertSame(0, $res1['retry_after']);

        $res2 = $limiter->hit('user:10', 3, 60);
        $this->assertTrue($res2['allowed']);
        $this->assertSame(1, $res2['remaining']);

        $res3 = $limiter->hit('user:10', 3, 60);
        $this->assertTrue($res3['allowed']);
        $this->assertSame(0, $res3['remaining']);
    }

    public function testBlocksWhenExceedingLimit(): void
    {
        $cache = new ArrayCache();
        $limiter = new CacheRateLimiter($cache);

        $limiter->hit('ip:127.0.0.1', 2, 60);
        $limiter->hit('ip:127.0.0.1', 2, 60);

        $res = $limiter->hit('ip:127.0.0.1', 2, 60);
        $this->assertFalse($res['allowed']);
        $this->assertSame(0, $res['remaining']);
        $this->assertGreaterThan(0, $res['retry_after']);
        $this->assertGreaterThanOrEqual(time(), $res['reset_at']);
    }

    public function testResetClearsRateLimit(): void
    {
        $cache = new ArrayCache();
        $limiter = new CacheRateLimiter($cache);

        $limiter->hit('ip:1.2.3.4', 1, 60);
        $blocked = $limiter->hit('ip:1.2.3.4', 1, 60);
        $this->assertFalse($blocked['allowed']);

        $limiter->reset('ip:1.2.3.4');

        $allowed = $limiter->hit('ip:1.2.3.4', 1, 60);
        $this->assertTrue($allowed['allowed']);
        $this->assertSame(0, $allowed['remaining']);
    }
}
