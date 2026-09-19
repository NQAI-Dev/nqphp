<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Cache;

use Nqphp\Core\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;

class ArrayCacheTest extends TestCase
{
    public function testSetAndGet(): void
    {
        $cache = new ArrayCache();
        $cache->set('foo', 'bar');

        $this->assertSame('bar', $cache->get('foo'));
    }

    public function testGetMissingKeyReturnsDefault(): void
    {
        $cache = new ArrayCache();

        $this->assertNull($cache->get('missing'));
        $this->assertSame('fallback', $cache->get('missing', 'fallback'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $cache = new ArrayCache();
        $cache->set('key', 42);

        $this->assertTrue($cache->has('key'));
        $this->assertFalse($cache->has('absent'));
    }

    public function testDelete(): void
    {
        $cache = new ArrayCache();
        $cache->set('key', 'value');
        $cache->delete('key');

        $this->assertFalse($cache->has('key'));
        $this->assertNull($cache->get('key'));
    }

    public function testClear(): void
    {
        $cache = new ArrayCache();
        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->clear();

        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    public function testTtlExpiry(): void
    {
        $cache = new ArrayCache();
        // TTL of 1 second; we force expiry by manipulating time via a second cache set
        // Instead: use TTL=0 which immediately deletes on set
        $cache->set('zero', 'val', 0);
        $this->assertFalse($cache->has('zero'));
        $this->assertNull($cache->get('zero'));
    }

    public function testNegativeTtlImmediatelyDeletes(): void
    {
        $cache = new ArrayCache();
        $cache->set('pre', 'existing');
        $cache->set('pre', 'existing', -5);

        $this->assertFalse($cache->has('pre'));
    }

    public function testNullTtlMeansNoExpiry(): void
    {
        $cache = new ArrayCache();
        $cache->set('permanent', 'data', null);

        $this->assertTrue($cache->has('permanent'));
        $this->assertSame('data', $cache->get('permanent'));
    }

    public function testStoresArbitraryValues(): void
    {
        $cache = new ArrayCache();
        $cache->set('int', 42);
        $cache->set('array', ['a' => 1]);
        $cache->set('null_val', null);

        $this->assertSame(42, $cache->get('int'));
        $this->assertSame(['a' => 1], $cache->get('array'));
        $this->assertNull($cache->get('null_val'));
        // null_val was set, so has() should be true even though value is null
        $this->assertTrue($cache->has('null_val'));
    }
}
