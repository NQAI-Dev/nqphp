<?php

declare(strict_types=1);

namespace Nqphp\Core\Filesystem;

use RuntimeException;

/**
 * Storage decorator that prevents write and delete mutations, ensuring read-only filesystem access.
 */
class ReadOnlyStorage implements StorageInterface
{
    public function __construct(
        private readonly StorageInterface $storage
    ) {
    }

    public function write(string $path, string $contents): void
    {
        throw new RuntimeException("Cannot write to '{$path}': storage is read-only.");
    }

    public function delete(string $path): bool
    {
        throw new RuntimeException("Cannot delete '{$path}': storage is read-only.");
    }

    public function read(string $path): string
    {
        return $this->storage->read($path);
    }

    public function has(string $path): bool
    {
        return $this->storage->has($path);
    }

    public function size(string $path): int
    {
        return $this->storage->size($path);
    }

    public function lastModified(string $path): int
    {
        return $this->storage->lastModified($path);
    }

    public function listContents(string $directory = ''): array
    {
        return $this->storage->listContents($directory);
    }
}
