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
}
