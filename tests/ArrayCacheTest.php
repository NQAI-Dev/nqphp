<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ArrayCache (in-memory CacheInterface backend).
 *
 * Covers the PSR-16-shaped contract: get/set roundtrip, defaults,
 * TTL expiry, delete, clear, has, and the ttl<=0 edge case.
 */
final class ArrayCacheTest extends TestCase
{
    public function testSetGetRoundtrip(): void
    {
        $cache = new ArrayCache();

        $cache->set('user.1', ['name' => 'Ed']);

        self::assertSame(['name' => 'Ed'], $cache->get('user.1'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $cache = new ArrayCache();

        self::assertNull($cache->get('missing'));
        self::assertSame('fallback', $cache->get('missing', 'fallback'));
    }

    public function testHas(): void
    {
        $cache = new ArrayCache();

        $cache->set('a', 1);

        self::assertTrue($cache->has('a'));
        self::assertFalse($cache->has('b'));
    }

    public function testDelete(): void
    {
        $cache = new ArrayCache();

        $cache->set('a', 1);
        $cache->delete('a');

        self::assertFalse($cache->has('a'));
    }

    public function testClear(): void
    {
        $cache = new ArrayCache();

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->clear();

        self::assertFalse($cache->has('a'));
        self::assertFalse($cache->has('b'));
    }

    public function testTtlExpiry(): void
    {
        $cache = new ArrayCache();

        $cache->set('ephemeral', 'x', 1);
        self::assertTrue($cache->has('ephemeral'));

        // Simulate passage of time past the TTL boundary.
        $ref = new \ReflectionProperty(ArrayCache::class, 'entries');
        $ref->setAccessible(true);
        $entries = $ref->getValue($cache);
        $entries['ephemeral']['expires'] = time() - 1;
        $ref->setValue($cache, $entries);

        self::assertFalse($cache->has('ephemeral'));
        self::assertSame('gone', $cache->get('ephemeral', 'gone'));
    }

    public function testNonPositiveTtlDeletesImmediately(): void
    {
        $cache = new ArrayCache();

        $cache->set('a', 1, 0);
        self::assertFalse($cache->has('a'));

        $cache->set('b', 2, -5);
        self::assertFalse($cache->has('b'));
    }

    public function testOverwrite(): void
    {
        $cache = new ArrayCache();

        $cache->set('k', 'v1');
        $cache->set('k', 'v2');

        self::assertSame('v2', $cache->get('k'));
    }

    public function testNullTtlMeansNoExpiry(): void
    {
        $cache = new ArrayCache();

        $cache->set('forever', 42, null);

        $ref = new \ReflectionProperty(ArrayCache::class, 'entries');
        $ref->setAccessible(true);
        $entries = $ref->getValue($cache);

        self::assertNull($entries['forever']['expires']);
        self::assertTrue($cache->has('forever'));
    }
}
