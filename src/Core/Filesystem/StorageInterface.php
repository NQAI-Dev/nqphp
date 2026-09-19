<?php

declare(strict_types=1);

namespace Nqphp\Core\Filesystem;

interface StorageInterface
{
    public function write(string $path, string $contents): void;

    public function read(string $path): string;

    public function has(string $path): bool;

    public function delete(string $path): bool;

    public function size(string $path): int;

    public function lastModified(string $path): int;

    /**
     * @return list<string>
     */
    public function listContents(string $directory = ''): array;
}
