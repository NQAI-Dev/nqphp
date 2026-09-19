<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Filesystem\LocalStorage;
use Nqphp\Core\Filesystem\StorageManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StorageTest extends TestCase
{
    private string $tempDir;
    private LocalStorage $storage;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_storage_test_' . uniqid();
        $this->storage = new LocalStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testWriteAndRead(): void
    {
        $this->storage->write('test.txt', 'Hello Storage');
        $this->assertTrue($this->storage->has('test.txt'));
        $this->assertSame('Hello Storage', $this->storage->read('test.txt'));
    }

    public function testNestedDirectoryCreationOnWrite(): void
    {
        $this->storage->write('nested/sub/data.json', '{"status":"ok"}');
        $this->assertTrue($this->storage->has('nested/sub/data.json'));
        $this->assertSame('{"status":"ok"}', $this->storage->read('nested/sub/data.json'));
    }

    public function testDeleteAndSize(): void
    {
        $this->storage->write('file_size.txt', '12345');
        $this->assertSame(5, $this->storage->size('file_size.txt'));
        $this->assertGreaterThan(0, $this->storage->lastModified('file_size.txt'));

        $this->assertTrue($this->storage->delete('file_size.txt'));
        $this->assertFalse($this->storage->has('file_size.txt'));
    }

    public function testListContents(): void
    {
        $this->storage->write('item1.txt', 'A');
        $this->storage->write('item2.txt', 'B');

        $contents = $this->storage->listContents();
        $this->assertContains('item1.txt', $contents);
        $this->assertContains('item2.txt', $contents);
    }

    public function testReadNonExistentThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->storage->read('missing.txt');
    }

    public function testStorageManager(): void
    {
        $manager = new StorageManager('local');
        $manager->mount('local', $this->storage);

        $disk = $manager->disk();
        $this->assertSame($this->storage, $disk);

        $disk->write('manager_test.txt', 'content');
        $this->assertTrue($this->storage->has('manager_test.txt'));
    }
}
