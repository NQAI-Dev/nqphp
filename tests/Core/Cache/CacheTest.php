<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Cache;

use Nqphp\Core\Cache\ArrayCache;
use Nqphp\Core\Cache\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function testDefaultStoreIsAccessible(): void
    {
        $manager = new Cache();
        $store = $manager->cache();

        $this->assertInstanceOf(ArrayCache::class, $store);
    }

    public function testNamedStoreIsIsolated(): void
    {
        $manager = new Cache();
        $manager->cache('users')->set('key', 'user-value');
        $manager->cache('posts')->set('key', 'post-value');

        $this->assertSame('user-value', $manager->cache('users')->get('key'));
        $this->assertSame('post-value', $manager->cache('posts')->get('key'));
    }

    public function testClearOnNamedStoreDoesNotAffectOthers(): void
    {
        $manager = new Cache();
        $manager->cache('a')->set('x', 1);
        $manager->cache('b')->set('x', 2);
        $manager->cache('a')->clear();

        $this->assertFalse($manager->cache('a')->has('x'));
        $this->assertTrue($manager->cache('b')->has('x'));
    }

    public function testStoresSameInstanceOnSubsequentCalls(): void
    {
        $manager = new Cache();
        $a = $manager->store('shared');
        $b = $manager->store('shared');

        $this->assertSame($a, $b);
    }

    public function testStoresListsInstantiatedStoreNames(): void
    {
        $manager = new Cache();
        $manager->store('alpha');
        $manager->store('beta');

        $this->assertContains('alpha', $manager->stores());
        $this->assertContains('beta', $manager->stores());
        $this->assertCount(2, $manager->stores());
    }

    public function testRememberComputesAndCachesValue(): void
    {
        $manager = new Cache();
        $calls = 0;

        $compute = static function () use (&$calls): string {
            $calls++;
            return 'computed';
        };

        $first = $manager->remember('key', 60, $compute);
        $second = $manager->remember('key', 60, $compute);

        $this->assertSame('computed', $first);
        $this->assertSame('computed', $second);
        $this->assertSame(1, $calls, 'Compute should be called only once');
    }

    public function testRememberUsesNamedStore(): void
    {
        $manager = new Cache();
        $manager->remember('item', null, fn() => 'stored', 'mystore');

        $this->assertTrue($manager->cache('mystore')->has('item'));
        $this->assertSame('stored', $manager->cache('mystore')->get('item'));
        $this->assertFalse($manager->cache()->has('item'));
    }
}
