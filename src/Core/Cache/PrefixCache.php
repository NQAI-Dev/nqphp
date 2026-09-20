<?php

declare(strict_types=1);

namespace Nqphp\Core\Cache;

/**
 * Cache decorator namespacing all keys with a custom prefix.
 */
class PrefixCache implements CacheInterface
{
    public function __construct(
        private readonly CacheInterface $inner,
        private readonly string $prefix
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->inner->get($this->prefix . $key, $default);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->inner->set($this->prefix . $key, $value, $ttl);
    }

    public function delete(string $key): void
    {
        $this->inner->delete($this->prefix . $key);
    }

    public function clear(): void
    {
        $this->inner->clear();
    }

    public function has(string $key): bool
    {
        return $this->inner->has($this->prefix . $key);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getInner(): CacheInterface
    {
        return $this->inner;
    }
}
