<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Cache;

use InvalidArgumentException;
use Nqphp\Core\Cache\ArrayCache;
use Nqphp\Core\Cache\CacheInterface;
use Nqphp\Core\Cache\TtlCapCache;
use PHPUnit\Framework\TestCase;

class TtlCapCacheTest extends TestCase
{
    public function testRejectsNonPositiveMaxTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TtlCapCache(new ArrayCache(), 0);
    }

    public function testCapsNullTtlToMaxTtl(): void
    {
        $inner = $this->createMock(CacheInterface::class);
        $inner->expects($this->once())
            ->method('set')
            ->with('key', 'val', 3600);

        $cache = new TtlCapCache($inner, 3600);
        $cache->set('key', 'val', null);
    }

    public function testCapsExcessiveTtlToMaxTtl(): void
    {
        $inner = $this->createMock(CacheInterface::class);
        $inner->expects($this->once())
            ->method('set')
            ->with('key', 'val', 3600);

        $cache = new TtlCapCache($inner, 3600);
        $cache->set('key', 'val', 86400);
    }

    public function testPreservesLowerTtl(): void
    {
        $inner = $this->createMock(CacheInterface::class);
        $inner->expects($this->once())
            ->method('set')
            ->with('key', 'val', 60);

        $cache = new TtlCapCache($inner, 3600);
        $cache->set('key', 'val', 60);
    }

    public function testProxiesOtherMethods(): void
    {
        $inner = new ArrayCache();
        $cache = new TtlCapCache($inner, 3600);

        $cache->set('foo', 'bar');
        $this->assertTrue($cache->has('foo'));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertSame(3600, $cache->getMaxTtl());

        $cache->delete('foo');
        $this->assertFalse($cache->has('foo'));

        $cache->set('a', 1);
        $cache->clear();
        $this->assertFalse($cache->has('a'));
    }
}
