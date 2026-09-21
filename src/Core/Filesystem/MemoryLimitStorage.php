<?php

declare(strict_types=1);

namespace Nqphp\Core\Filesystem;

use InvalidArgumentException;
use RuntimeException;

/**
 * Storage decorator that enforces a maximum aggregate byte quota on stored files.
 */
class MemoryLimitStorage implements StorageInterface
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly int $maxBytes
    ) {
        if ($this->maxBytes < 0) {
            throw new InvalidArgumentException('maxBytes must be non-negative.');
        }
    }

    public function write(string $path, string $contents): void
    {
        $newSize = strlen($contents);
        $currentSize = $this->storage->has($path) ? $this->storage->size($path) : 0;
        $totalUsed = $this->getTotalBytesUsed();
        $projected = $totalUsed - $currentSize + $newSize;

        if ($projected > $this->maxBytes) {
            throw new RuntimeException(sprintf(
                'Storage quota exceeded: projected size %d bytes exceeds limit of %d bytes.',
                $projected,
                $this->maxBytes
            ));
        }

        $this->storage->write($path, $contents);
    }

    public function read(string $path): string
    {
        return $this->storage->read($path);
    }

    public function has(string $path): bool
    {
        return $this->storage->has($path);
    }

    public function delete(string $path): bool
    {
        return $this->storage->delete($path);
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

    public function getTotalBytesUsed(): int
    {
        $files = $this->storage->listContents('');
        $bytes = 0;
        foreach ($files as $file) {
            if ($this->storage->has($file)) {
                $bytes += $this->storage->size($file);
            }
        }
        return $bytes;
    }

    public function getAvailableBytes(): int
    {
        return max(0, $this->maxBytes - $this->getTotalBytesUsed());
    }

    public function getMaxBytes(): int
    {
        return $this->maxBytes;
    }
}
