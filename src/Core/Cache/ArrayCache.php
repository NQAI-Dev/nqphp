<?php

declare(strict_types=1);

namespace Nqphp\Core\Cache;

/**
 * In-memory cache with TTL support.
 *
 * ArrayCache is a PSR-16-shaped minimal cache: get / set / delete /
 * clear / has. Entries store their expiry timestamp; expired entries
 * are treated as absent on read and lazily evicted on write.
 *
 * Intended as the default backend for the CacheInterface contract;
 * swap for a Redis/file backend later without touching callers.
 */
final class ArrayCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expires: ?int}> */
    private array $entries = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->entries[$key]['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        if ($ttl !== null && $ttl <= 0) {
            $this->delete($key);
            return;
        }

        $this->entries[$key] = [
            'value' => $value,
            'expires' => $ttl === null ? null : time() + $ttl,
        ];
    }

    public function delete(string $key): void
    {
        unset($this->entries[$key]);
    }

    public function clear(): void
    {
        $this->entries = [];
    }

    public function has(string $key): bool
    {
        if (!\array_key_exists($key, $this->entries)) {
            return false;
        }

        $expires = $this->entries[$key]['expires'];
        if ($expires !== null && $expires <= time()) {
            unset($this->entries[$key]);
            return false;
        }

        return true;
    }
}
