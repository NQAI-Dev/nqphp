<?php

declare(strict_types=1);

namespace Nqphp\Core\Cache;

/**
 * Minimal cache contract (PSR-16-shaped).
 *
 * TTL is measured in whole seconds; null means "no expiry".
 * Implementations must treat expired entries as absent.
 */
interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, ?int $ttl = null): void;

    public function delete(string $key): void;

    public function clear(): void;

    public function has(string $key): bool;
}
