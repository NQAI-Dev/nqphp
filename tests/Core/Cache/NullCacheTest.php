<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Cache;

use Nqphp\Core\Cache\CacheInterface;
use Nqphp\Core\Cache\NullCache;
use PHPUnit\Framework\TestCase;

class NullCacheTest extends TestCase
{
    private NullCache $cache;

    protected function setUp(): void
    {
        $this->cache = new NullCache();
    }

    public function testImplementsCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->cache);
    }

    public function testGetAlwaysReturnsDefault(): void
    {
        $this->assertNull($this->cache->get('any_key'));
        $this->assertSame('default_val', $this->cache->get('any_key', 'default_val'));
    }

    public function testHasAlwaysReturnsFalse(): void
    {
        $this->assertFalse($this->cache->has('any_key'));

        $this->cache->set('any_key', 'stored_value');
        $this->assertFalse($this->cache->has('any_key'));
        $this->assertNull($this->cache->get('any_key'));
    }

    public function testOperationsAreSafeNoOps(): void
    {
        $this->cache->set('key', 'value', 3600);
        $this->cache->delete('key');
        $this->cache->clear();

        $this->assertFalse($this->cache->has('key'));
    }
}
