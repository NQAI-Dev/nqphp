<?php

declare(strict_types=1);

namespace Nqphp\Core\Filesystem;

/**
 * Storage decorator isolating operations within a designated subpath prefix.
 */
class PrefixStorage implements StorageInterface
{
    private string $prefix;

    public function __construct(
        private readonly StorageInterface $storage,
        string $prefix
    ) {
        $trimmed = trim($prefix, '/');
        $this->prefix = $trimmed === '' ? '' : $trimmed . '/';
    }

    public function write(string $path, string $contents): void
    {
        $this->storage->write($this->prefixPath($path), $contents);
    }

    public function read(string $path): string
    {
        return $this->storage->read($this->prefixPath($path));
    }

    public function has(string $path): bool
    {
        return $this->storage->has($this->prefixPath($path));
    }

    public function delete(string $path): bool
    {
        return $this->storage->delete($this->prefixPath($path));
    }

    public function size(string $path): int
    {
        return $this->storage->size($this->prefixPath($path));
    }

    public function lastModified(string $path): int
    {
        return $this->storage->lastModified($this->prefixPath($path));
    }

    /**
     * @return list<string>
     */
    public function listContents(string $directory = ''): array
    {
        $cleanDir = trim($directory, '/');
        $targetDir = $cleanDir === '' ? rtrim($this->prefix, '/') : $this->prefix . $cleanDir;
        $items = $this->storage->listContents($targetDir);

        $prefixLen = strlen($this->prefix);
        $result = [];

        foreach ($items as $item) {
            if ($this->prefix === '') {
                $result[] = $item;
                continue;
            }

            if (str_starts_with($item, $this->prefix)) {
                $result[] = substr($item, $prefixLen);
            }
        }

        return array_values($result);
    }

    private function prefixPath(string $path): string
    {
        $clean = ltrim($path, '/');
        return $this->prefix . $clean;
    }
}
