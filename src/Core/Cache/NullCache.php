<?php

declare(strict_types=1);

namespace Nqphp\Core\Cache;

/**
 * Blackhole cache implementation for testing, dev environments, or disabling caching.
 */
class NullCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        // No-op
    }

    public function delete(string $key): void
    {
        // No-op
    }

    public function clear(): void
    {
        // No-op
    }

    public function has(string $key): bool
    {
        return false;
    }
}
