<?php

declare(strict_types=1);

namespace Nqphp\Core\Session;

/**
 * In-memory implementation of SessionInterface for testing and stateless environments.
 */
class ArraySession implements SessionInterface
{
    private bool $started = false;

    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, list<mixed>> */
    private array $flashes = [];

    public function start(): void
    {
        $this->started = true;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function destroy(): void
    {
        $this->data = [];
        $this->flashes = [];
        $this->started = false;
    }

    public function addFlash(string $key, mixed $message): void
    {
        if (!isset($this->flashes[$key])) {
            $this->flashes[$key] = [];
        }
        $this->flashes[$key][] = $message;
    }

    public function getFlash(string $key, array $default = []): array
    {
        if (!isset($this->flashes[$key])) {
            return $default;
        }

        $messages = $this->flashes[$key];
        unset($this->flashes[$key]);

        return $messages;
    }

    public function hasFlash(string $key): bool
    {
        return !empty($this->flashes[$key]);
    }

    /**
     * Return all session data (useful for inspection in tests).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}
