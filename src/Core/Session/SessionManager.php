<?php

declare(strict_types=1);

namespace Nqphp\Core\Session;

class SessionManager implements SessionInterface
{
    private bool $started = false;

    public function isStarted(): bool
    {
        return $this->started || session_status() === PHP_SESSION_ACTIVE;
    }

    public function start(): void
    {
        if ($this->isStarted()) {
            return;
        }

        if (headers_sent()) {
            throw new \RuntimeException('Cannot start session: headers already sent.');
        }

        session_start();
        $this->started = true;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $this->start();
        return array_key_exists($key, $_SESSION);
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $this->start();
        $_SESSION = [];
    }
    
    public function destroy(): void
    {
        if (!$this->isStarted()) {
            return;
        }
        
        session_destroy();
        $this->started = false;
        $_SESSION = [];
    }
}
