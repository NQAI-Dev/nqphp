<?php

declare(strict_types=1);

namespace Nqphp\Core\Filesystem;

use RuntimeException;

/**
 * In-memory storage implementation of StorageInterface.
 * Ideal for isolated testing and fast ephemeral file operations.
 */
class InMemoryStorage implements StorageInterface
{
    /**
     * @var array<string, array{contents: string, mtime: int}>
     */
    private array $files = [];

    public function write(string $path, string $contents): void
    {
        $normalized = $this->normalizePath($path);
        $this->files[$normalized] = [
            'contents' => $contents,
            'mtime' => time(),
        ];
    }

    public function read(string $path): string
    {
        $normalized = $this->normalizePath($path);
        if (!isset($this->files[$normalized])) {
            throw new RuntimeException("File not found: {$path}");
        }

        return $this->files[$normalized]['contents'];
    }

    public function has(string $path): bool
    {
        $normalized = $this->normalizePath($path);
        return isset($this->files[$normalized]);
    }

    public function delete(string $path): bool
    {
        $normalized = $this->normalizePath($path);
        if (isset($this->files[$normalized])) {
            unset($this->files[$normalized]);
            return true;
        }

        return false;
    }

    public function size(string $path): int
    {
        $normalized = $this->normalizePath($path);
        if (!isset($this->files[$normalized])) {
            throw new RuntimeException("File not found: {$path}");
        }

        return strlen($this->files[$normalized]['contents']);
    }

    public function lastModified(string $path): int
    {
        $normalized = $this->normalizePath($path);
        if (!isset($this->files[$normalized])) {
            throw new RuntimeException("File not found: {$path}");
        }

        return $this->files[$normalized]['mtime'];
    }

    public function listContents(string $directory = ''): array
    {
        $dir = $this->normalizePath($directory);
        $prefix = $dir === '' ? '' : $dir . '/';
        $prefixLen = strlen($prefix);

        $results = [];
        foreach (array_keys($this->files) as $path) {
            if ($prefix === '' || str_starts_with($path, $prefix)) {
                $sub = substr($path, $prefixLen);
                $parts = explode('/', $sub);
                $entry = $parts[0];
                if ($entry !== '' && !in_array($entry, $results, true)) {
                    $results[] = $prefix . $entry;
                }
            }
        }

        sort($results);
        return $results;
    }

    public function clear(): void
    {
        $this->files = [];
    }

    private function normalizePath(string $path): string
    {
        $clean = str_replace(['\\', '../', '..'], ['/', '', ''], $path);
        return trim($clean, '/');
    }
}
