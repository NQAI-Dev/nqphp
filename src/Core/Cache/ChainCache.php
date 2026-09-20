<?php

declare(strict_types=1);

namespace Nqphp\Core\Cache;

/**
 * Cache implementation that chains multiple cache stores together.
 * Reads check caches sequentially (promoting hits to faster earlier layers).
 * Writes, deletes, and clears propagate across all configured cache drivers.
 */
class ChainCache implements CacheInterface
{
    /** @var list<CacheInterface> */
    private array $stores;

    public function __construct(CacheInterface ...$stores)
    {
        $this->stores = array_values($stores);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $missedStores = [];

        foreach ($this->stores as $store) {
            if ($store->has($key)) {
                $value = $store->get($key);

                // Backfill value to earlier cache layers that missed it
                foreach ($missedStores as $earlierStore) {
                    $earlierStore->set($key, $value);
                }

                return $value;
            }

            $missedStores[] = $store;
        }

        return $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        foreach ($this->stores as $store) {
            $store->set($key, $value, $ttl);
        }
    }

    public function has(string $key): bool
    {
        foreach ($this->stores as $store) {
            if ($store->has($key)) {
                return true;
            }
        }

        return false;
    }

    public function delete(string $key): void
    {
        foreach ($this->stores as $store) {
            $store->delete($key);
        }
    }

    public function clear(): void
    {
        foreach ($this->stores as $store) {
            $store->clear();
        }
    }
}
