<?php

declare(strict_types=1);

namespace Nqphp\Core\Cache;

/**
 * Cache manager: namespaced stores + get-or-compute helper.
 *
 * Stores are created lazily on first use and kept for the object's
 * lifetime; each store is a separate ArrayCache whose keys are
 * prefixed with the store name, so `cache('rendered')->clear()` never
 * touches the default store.
 *
 *   $cache->cache('weather')->set('london', $data, 600);
 *   $html = $cache->remember('menu', 300, fn () => renderMenu());
 */
final class Cache
{
    /** @var array<string, CacheInterface> */
    private array $stores = [];

    /** Default store accessor. */
    public function cache(?string $store = null): CacheInterface
    {
        return $this->store($store ?? 'default');
    }

    /** Named store accessor (created on first call). */
    public function store(string $name): CacheInterface
    {
        if (!isset($this->stores[$name])) {
            $this->stores[$name] = new ArrayCache();
        }
        return $this->stores[$name];
    }

    /**
     * Names of all stores instantiated so far (introspection/tests).
     *
     * @return list<string>
     */
    public function stores(): array
    {
        return array_keys($this->stores);
    }

    /**
     * Get-or-compute: return the cached value for $key, or compute it
     * via $compute, store it with $ttl and return it. Useful for
     * expensive per-request work (rendering, aggregation queries).
     *
     * @template T
     * @param callable():T $compute
     * @return T
     */
    public function remember(string $key, ?int $ttl, callable $compute, ?string $store = null): mixed
    {
        $cached = $this->cache($store)->get($key, '__NQPHP_MISS__');
        if ($cached !== '__NQPHP_MISS__') {
            return $cached;
        }
        $value = $compute();
        $this->cache($store)->set($key, $value, $ttl);
        return $value;
    }
}
