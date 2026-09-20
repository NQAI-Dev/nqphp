<?php

declare(strict_types=1);

namespace Nqphp\Core\Session;

use Nqphp\Core\Cache\CacheInterface;

/**
 * Session implementation backed by a CacheInterface adapter (Redis, Memcached, FileCache, etc.).
 */
class CacheSession implements SessionInterface
{
    private bool $started = false;

    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, list<mixed>> */
    private array $flashes = [];

    public function __construct(
        private readonly CacheInterface $cache,
        private string $sessionId,
        private readonly int $ttl = 7200,
        private readonly string $prefix = 'session:'
    ) {
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $cached = $this->cache->get($this->prefix . $this->sessionId);
        if (is_array($cached)) {
            $this->data = $cached['data'] ?? [];
            $this->flashes = $cached['flashes'] ?? [];
        } else {
            $this->data = [];
            $this->flashes = [];
        }

        $this->started = true;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $this->data[$key] = $value;
        $this->persist();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function has(string $key): bool
    {
        $this->ensureStarted();
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): void
    {
        $this->ensureStarted();
        unset($this->data[$key]);
        $this->persist();
    }

    public function clear(): void
    {
        $this->ensureStarted();
        $this->data = [];
        $this->persist();
    }

    public function destroy(): void
    {
        $this->cache->delete($this->prefix . $this->sessionId);
        $this->data = [];
        $this->flashes = [];
        $this->started = false;
    }

    public function addFlash(string $key, mixed $message): void
    {
        $this->ensureStarted();
        if (!isset($this->flashes[$key])) {
            $this->flashes[$key] = [];
        }
        $this->flashes[$key][] = $message;
        $this->persist();
    }

    public function getFlash(string $key, array $default = []): array
    {
        $this->ensureStarted();
        if (!isset($this->flashes[$key])) {
            return $default;
        }

        $messages = $this->flashes[$key];
        unset($this->flashes[$key]);
        $this->persist();

        return $messages;
    }

    public function hasFlash(string $key): bool
    {
        $this->ensureStarted();
        return !empty($this->flashes[$key]);
    }

    public function regenerate(bool $destroyOldSession = false): bool
    {
        $this->ensureStarted();

        if ($destroyOldSession) {
            $this->cache->delete($this->prefix . $this->sessionId);
        }

        $this->sessionId = bin2hex(random_bytes(16));
        $this->persist();

        return true;
    }

    public function getId(): string
    {
        return $this->sessionId;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->ensureStarted();
        return $this->data;
    }

    private function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }

    private function persist(): void
    {
        $this->cache->set(
            $this->prefix . $this->sessionId,
            [
                'data' => $this->data,
                'flashes' => $this->flashes,
            ],
            $this->ttl
        );
    }
}
