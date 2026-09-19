<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Cache\Cache;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Cache manager (namespaced stores + remember())
 * and its Kernel wiring. ArrayCache TTL basics are covered by
 * ArrayCacheTest.
 */
final class CacheManagerTest extends TestCase
{
    public function testStoresAreIsolatedByName(): void
    {
        $cache = new Cache();

        $cache->cache()->set('shared-key', 'default-store');
        $cache->cache('rendered')->set('shared-key', 'rendered-store');

        self::assertSame('default-store', $cache->cache()->get('shared-key'));
        self::assertSame('rendered-store', $cache->cache('rendered')->get('shared-key'));

        $cache->cache('rendered')->clear();
        self::assertFalse($cache->cache('rendered')->has('shared-key'));
        self::assertTrue($cache->cache()->has('shared-key'));
    }

    public function testStoreReturnsSameInstancePerName(): void
    {
        $cache = new Cache();

        self::assertSame($cache->store('weather'), $cache->store('weather'));
        self::assertSame(['weather'], $cache->stores());
    }

    public function testRememberComputesOnceThenServesCache(): void
    {
        $cache = new Cache();
        $calls = 0;
        $compute = function () use (&$calls) {
            ++$calls;

            return 'computed-' . $calls;
        };

        self::assertSame('computed-1', $cache->remember('expensive', 60, $compute));
        self::assertSame('computed-1', $cache->remember('expensive', 60, $compute));
        self::assertSame(1, $calls);
    }

    public function testRememberRecomputesWhenEntryNotCached(): void
    {
        $cache = new Cache();

        // Non-positive TTL → nothing is stored → every call recomputes.
        $first = $cache->remember('short', 0, fn () => uniqid('', true));
        $second = $cache->remember('short', 0, fn () => uniqid('', true));

        self::assertNotSame($first, $second);
    }

    public function testRememberTargetsNamedStore(): void
    {
        $cache = new Cache();

        $cache->remember('k', 60, fn () => 'in-store', 'special');

        self::assertSame('in-store', $cache->cache('special')->get('k'));
        self::assertFalse($cache->cache()->has('k'));
    }

    // ---- Kernel wiring ----------------------------------------------

    public function testKernelCacheAccessorReturnsStableManager(): void
    {
        $kernel = new Kernel(__DIR__ . '/..');

        self::assertSame($kernel->cache(), $kernel->cache());
    }

    public function testKernelCacheUsable(): void
    {
        $kernel = new Kernel(__DIR__ . '/..');

        $store = $kernel->cache()->cache();
        $store->set('feature-key', 'value', 60);

        self::assertSame('value', $store->get('feature-key'));
    }
}
