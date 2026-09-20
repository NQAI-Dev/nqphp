<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Cache;

use Nqphp\Core\Cache\FileCache;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    private string $cacheDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/nqphp_cache_test_' . uniqid();
        $this->cache = new FileCache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        if (is_dir($this->cacheDir)) {
            @rmdir($this->cacheDir);
        }
    }

    public function testGetReturnsDefaultWhenKeyNotExists(): void
    {
        $this->assertNull($this->cache->get('missing_key'));
        $this->assertSame('custom_default', $this->cache->get('missing_key', 'custom_default'));
        $this->assertFalse($this->cache->has('missing_key'));
    }

    public function testSetAndGetBasicTypes(): void
    {
        $this->cache->set('string_key', 'hello world');
        $this->assertSame('hello world', $this->cache->get('string_key'));
        $this->assertTrue($this->cache->has('string_key'));

        $this->cache->set('array_key', ['a' => 1, 'b' => 2]);
        $this->assertSame(['a' => 1, 'b' => 2], $this->cache->get('array_key'));

        $this->cache->set('int_key', 42);
        $this->assertSame(42, $this->cache->get('int_key'));
    }

    public function testExpiresWithTtl(): void
    {
        $this->cache->set('expiring_key', 'temp_value', 1);
        $this->assertTrue($this->cache->has('expiring_key'));
        $this->assertSame('temp_value', $this->cache->get('expiring_key'));

        sleep(2);

        $this->assertFalse($this->cache->has('expiring_key'));
        $this->assertNull($this->cache->get('expiring_key'));
    }

    public function testDeleteKey(): void
    {
        $this->cache->set('key_to_delete', 'value');
        $this->assertTrue($this->cache->has('key_to_delete'));

        $this->cache->delete('key_to_delete');
        $this->assertFalse($this->cache->has('key_to_delete'));
        $this->assertNull($this->cache->get('key_to_delete'));
    }

    public function testClearRemovesAllEntries(): void
    {
        $this->cache->set('key1', 'val1');
        $this->cache->set('key2', 'val2');
        $this->cache->set('key3', 'val3');

        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));

        $this->cache->clear();

        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
        $this->assertFalse($this->cache->has('key3'));
    }
}
