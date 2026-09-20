<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Filesystem;

use InvalidArgumentException;
use Nqphp\Core\Filesystem\InMemoryStorage;
use Nqphp\Core\Filesystem\LocalStorage;
use Nqphp\Core\Filesystem\StorageManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LocalStorageAndManagerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_storage_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testLocalStorageCrudOperations(): void
    {
        $storage = new LocalStorage($this->tempDir);

        $this->assertFalse($storage->has('test.txt'));
        $storage->write('test.txt', 'hello filesystem');

        $this->assertTrue($storage->has('test.txt'));
        $this->assertSame('hello filesystem', $storage->read('test.txt'));
        $this->assertSame(16, $storage->size('test.txt'));
        $this->assertGreaterThan(0, $storage->lastModified('test.txt'));

        $this->assertTrue($storage->delete('test.txt'));
        $this->assertFalse($storage->has('test.txt'));
        $this->assertFalse($storage->delete('test.txt'));
    }

    public function testLocalStorageSubdirectoryAndList(): void
    {
        $storage = new LocalStorage($this->tempDir);

        $storage->write('nested/dir/file1.txt', 'file 1');
        $storage->write('nested/dir/file2.txt', 'file 2');

        $this->assertTrue($storage->has('nested/dir/file1.txt'));
        $this->assertSame('file 1', $storage->read('nested/dir/file1.txt'));

        $files = $storage->listContents('nested/dir');
        sort($files);
        $this->assertSame(['nested/dir/file1.txt', 'nested/dir/file2.txt'], $files);
    }

    public function testLocalStorageThrowsOnMissingFile(): void
    {
        $storage = new LocalStorage($this->tempDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found');
        $storage->read('absent.txt');
    }

    public function testStorageManagerMountAndResolve(): void
    {
        $manager = new StorageManager('local');
        $local = new InMemoryStorage();
        $backup = new InMemoryStorage();

        $manager->mount('local', $local);
        $manager->mount('backup', $backup);

        $this->assertSame($local, $manager->disk());
        $this->assertSame($local, $manager->disk('local'));
        $this->assertSame($backup, $manager->disk('backup'));
    }

    public function testStorageManagerThrowsOnUnmountedDisk(): void
    {
        $manager = new StorageManager('local');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disk [unknown] is not mounted');
        $manager->disk('unknown');
    }
}
