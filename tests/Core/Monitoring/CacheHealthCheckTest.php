<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Monitoring;

use Exception;
use Nqphp\Core\Cache\ArrayCache;
use Nqphp\Core\Cache\CacheInterface;
use Nqphp\Core\Monitoring\CacheHealthCheck;
use PHPUnit\Framework\TestCase;

class CacheHealthCheckTest extends TestCase
{
    public function testReturnsOkOnHealthyCache(): void
    {
        $cache = new ArrayCache();
        $check = new CacheHealthCheck($cache);

        $this->assertSame('cache', $check->getName());

        $result = $check->check();
        $this->assertSame('ok', $result['status']);
        $this->assertArrayHasKey('meta', $result);
        $this->assertSame('ArrayCache', $result['meta']['driver']);
    }

    public function testReturnsDownOnException(): void
    {
        $mock = $this->createMock(CacheInterface::class);
        $mock->method('set')->willThrowException(new Exception('Connection refused'));

        $check = new CacheHealthCheck($mock, 'redis_cache');
        $this->assertSame('redis_cache', $check->getName());

        $result = $check->check();
        $this->assertSame('down', $result['status']);
        $this->assertStringContainsString('Connection refused', $result['message']);
    }

    public function testReturnsDegradedOnMismatch(): void
    {
        $mock = $this->createMock(CacheInterface::class);
        $mock->method('get')->willReturn('mismatched_value');

        $check = new CacheHealthCheck($mock);
        $result = $check->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertStringContainsString('retrieved value did not match', $result['message']);
    }
}
