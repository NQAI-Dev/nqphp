<?php

declare(strict_types=1);

namespace Nqphp\Core\Session;

/**
 * Session contract for storing and retrieving conversational state.
 */
interface SessionInterface
{
    public function start(): void;

    public function set(string $key, mixed $value): void;

    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    public function remove(string $key): void;

    public function clear(): void;

    public function destroy(): void;

    public function isStarted(): bool;

    /**
     * Add a flash message for the given key.
     */
    public function addFlash(string $key, mixed $message): void;

    /**
     * Get flash messages for the given key and clear them from the session.
     *
     * @return array<array-key, mixed>
     */
    public function getFlash(string $key, array $default = []): array;

    /**
     * Check if flash messages exist for the given key.
     */
    public function hasFlash(string $key): bool;
}
