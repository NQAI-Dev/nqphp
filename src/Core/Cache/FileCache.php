<?php

declare(strict_types=1);

namespace Nqphp\Core\Cache;

/**
 * File-based cache implementation with atomic writes, TTL expiration, and auto directory hierarchy.
 */
class FileCache implements CacheInterface
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);
        if (!is_file($file)) {
            return $default;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return $default;
        }

        /** @var array{expires_at: int|null, data: mixed}|null $payload */
        $payload = @unserialize($content);
        if (!is_array($payload) || !array_key_exists('expires_at', $payload) || !array_key_exists('data', $payload)) {
            $this->delete($key);
            return $default;
        }

        if ($payload['expires_at'] !== null && time() >= $payload['expires_at']) {
            $this->delete($key);
            return $default;
        }

        return $payload['data'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $file = $this->getFilePath($key);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $expiresAt = $ttl !== null ? time() + $ttl : null;
        $payload = serialize([
            'expires_at' => $expiresAt,
            'data' => $value,
        ]);

        $tmpFile = $file . '.' . uniqid('tmp_', true);
        file_put_contents($tmpFile, $payload, LOCK_EX);
        rename($tmpFile, $file);
    }

    public function delete(string $key): void
    {
        $file = $this->getFilePath($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function clear(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    private function getFilePath(string $key): string
    {
        $hash = sha1($key);
        $subDir = substr($hash, 0, 2);
        return $this->directory . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . $hash . '.cache';
    }
}
