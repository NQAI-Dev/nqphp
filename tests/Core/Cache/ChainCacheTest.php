<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Cache;

use Nqphp\Core\Cache\ArrayCache;
use Nqphp\Core\Cache\CacheInterface;
use Nqphp\Core\Cache\ChainCache;
use PHPUnit\Framework\TestCase;

class ChainCacheTest extends TestCase
{
    public function testImplementsCacheInterface(): void
    {
        $cache = new ChainCache();
        $this->assertInstanceOf(CacheInterface::class, $cache);
    }

    public function testSetAndGetAcrossStores(): void
    {
        $fast = new ArrayCache();
        $slow = new ArrayCache();
        $chain = new ChainCache($fast, $slow);

        $chain->set('user_1', 'Alice');

        $this->assertSame('Alice', $fast->get('user_1'));
        $this->assertSame('Alice', $slow->get('user_1'));
        $this->assertSame('Alice', $chain->get('user_1'));
    }

    public function testBackfillsEarlierLayerOnHit(): void
    {
        $fast = new ArrayCache();
        $slow = new ArrayCache();

        $slow->set('config_app', 'production');

        $this->assertFalse($fast->has('config_app'));

        $chain = new ChainCache($fast, $slow);

        $this->assertSame('production', $chain->get('config_app'));
        $this->assertTrue($fast->has('config_app'));
        $this->assertSame('production', $fast->get('config_app'));
    }

    public function testHasChecksStoresSequentially(): void
    {
        $fast = new ArrayCache();
        $slow = new ArrayCache();
        $slow->set('item', 'val');

        $chain = new ChainCache($fast, $slow);

        $this->assertTrue($chain->has('item'));
        $this->assertFalse($chain->has('non_existent'));
    }

    public function testDeletePropagatesToAllStores(): void
    {
        $fast = new ArrayCache();
        $slow = new ArrayCache();
        $chain = new ChainCache($fast, $slow);

        $chain->set('item', 123);
        $chain->delete('item');

        $this->assertFalse($fast->has('item'));
        $this->assertFalse($slow->has('item'));
        $this->assertFalse($chain->has('item'));
    }

    public function testClearFlushesAllStores(): void
    {
        $fast = new ArrayCache();
        $slow = new ArrayCache();
        $chain = new ChainCache($fast, $slow);

        $chain->set('a', 1);
        $chain->set('b', 2);
        $chain->clear();

        $this->assertFalse($fast->has('a'));
        $this->assertFalse($slow->has('b'));
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        $fast = new ArrayCache();
        $slow = new ArrayCache();
        $chain = new ChainCache($fast, $slow);

        $this->assertSame('fallback', $chain->get('missing', 'fallback'));
    }
}
