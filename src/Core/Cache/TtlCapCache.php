<?php

declare(strict_types=1);

namespace Nqphp\Core\Cache;

use InvalidArgumentException;

/**
 * Cache decorator that enforces a maximum upper bound on entry TTL.
 * Any TTL requested higher than $maxTtl (or infinite/null TTL) is capped to $maxTtl.
 */
class TtlCapCache implements CacheInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $maxTtl
    ) {
        if ($this->maxTtl <= 0) {
            throw new InvalidArgumentException('maxTtl must be greater than zero.');
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $cappedTtl = ($ttl === null || $ttl > $this->maxTtl) ? $this->maxTtl : $ttl;
        $this->cache->set($key, $value, $cappedTtl);
    }

    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }

    public function clear(): void
    {
        $this->cache->clear();
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function getMaxTtl(): int
    {
        return $this->maxTtl;
    }
}
