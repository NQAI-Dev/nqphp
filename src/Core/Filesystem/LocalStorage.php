<?php

declare(strict_types=1);

namespace Nqphp\Core\Filesystem;

use RuntimeException;

final class LocalStorage implements StorageInterface
{
    private string $root;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/\\');
        if (!is_dir($this->root)) {
            mkdir($this->root, 0775, true);
        }
    }

    public function write(string $path, string $contents): void
    {
        $fullPath = $this->resolvePath($path);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $res = file_put_contents($fullPath, $contents, LOCK_EX);
        if ($res === false) {
            throw new RuntimeException("Failed to write to file: {$fullPath}");
        }
    }

    public function read(string $path): string
    {
        $fullPath = $this->resolvePath($path);
        if (!is_file($fullPath)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $contents = file_get_contents($fullPath);
        if ($contents === false) {
            throw new RuntimeException("Failed to read file: {$path}");
        }

        return $contents;
    }

    public function has(string $path): bool
    {
        return file_exists($this->resolvePath($path));
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->resolvePath($path);
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function size(string $path): int
    {
        $fullPath = $this->resolvePath($path);
        if (!file_exists($fullPath)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $size = filesize($fullPath);
        return $size !== false ? $size : 0;
    }

    public function lastModified(string $path): int
    {
        $fullPath = $this->resolvePath($path);
        if (!file_exists($fullPath)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $mtime = filemtime($fullPath);
        return $mtime !== false ? $mtime : 0;
    }

    public function listContents(string $directory = ''): array
    {
        $fullDir = $this->resolvePath($directory);
        if (!is_dir($fullDir)) {
            return [];
        }

        $files = scandir($fullDir);
        if ($files === false) {
            return [];
        }

        $results = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $rel = trim($directory === '' ? $file : $directory . '/' . $file, '/');
            $results[] = $rel;
        }

        return $results;
    }

    private function resolvePath(string $path): string
    {
        $clean = ltrim(str_replace(['../', '..\\'], '', $path), '/\\');
        return $this->root . '/' . $clean;
    }
}
