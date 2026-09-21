<?php

declare(strict_types=1);

namespace Nqphp\Core\Filesystem;

use RuntimeException;

/**
 * Null/blackhole storage implementation for testing or discarding writes.
 */
class NullStorage implements StorageInterface
{
    public function write(string $path, string $contents): void
    {
        // Discard
    }

    public function read(string $path): string
    {
        throw new RuntimeException("Файл не найден в NullStorage: {$path}");
    }

    public function has(string $path): bool
    {
        return false;
    }

    public function delete(string $path): bool
    {
        return true;
    }

    public function size(string $path): int
    {
        return 0;
    }

    public function lastModified(string $path): int
    {
        return 0;
    }

    /**
     * @return list<string>
     */
    public function listContents(string $directory = ''): array
    {
        return [];
    }
}
