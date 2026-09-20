<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Cache;

use Nqphp\Core\Cache\ArrayCache;
use Nqphp\Core\Cache\PrefixCache;
use PHPUnit\Framework\TestCase;

class PrefixCacheTest extends TestCase
{
    public function testGetSetHasDeleteWithPrefix(): void
    {
        $inner = new ArrayCache();
        $cache = new PrefixCache($inner, 'app_v1:');

        $this->assertSame('app_v1:', $cache->getPrefix());
        $this->assertSame($inner, $cache->getInner());

        $this->assertFalse($cache->has('user_42'));
        $this->assertNull($cache->get('user_42'));
        $this->assertSame('fallback', $cache->get('user_42', 'fallback'));

        $cache->set('user_42', ['name' => 'Alice'], 60);

        $this->assertTrue($cache->has('user_42'));
        $this->assertSame(['name' => 'Alice'], $cache->get('user_42'));

        // Check that the underlying cache stored it with the exact prefix
        $this->assertTrue($inner->has('app_v1:user_42'));
        $this->assertSame(['name' => 'Alice'], $inner->get('app_v1:user_42'));
        $this->assertFalse($inner->has('user_42'));

        $cache->delete('user_42');
        $this->assertFalse($cache->has('user_42'));
        $this->assertFalse($inner->has('app_v1:user_42'));
    }

    public function testClearDelegatesToInner(): void
    {
        $inner = new ArrayCache();
        $cache = new PrefixCache($inner, 'test:');

        $cache->set('item', 'val');
        $this->assertTrue($cache->has('item'));

        $cache->clear();
        $this->assertFalse($cache->has('item'));
    }
}
