<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Filesystem;

use Nqphp\Core\Filesystem\NullStorage;
use Nqphp\Core\Filesystem\StorageInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class NullStorageTest extends TestCase
{
    private NullStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new NullStorage();
    }

    public function testImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(StorageInterface::class, $this->storage);
    }

    public function testWriteAndHasAlwaysFalse(): void
    {
        $this->storage->write('test.txt', 'hello');
        $this->assertFalse($this->storage->has('test.txt'));
    }

    public function testReadThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Файл не найден в NullStorage: test.txt');
        $this->storage->read('test.txt');
    }

    public function testDeleteReturnsTrue(): void
    {
        $this->assertTrue($this->storage->delete('nonexistent.txt'));
    }

    public function testSizeAndLastModifiedReturnZero(): void
    {
        $this->assertSame(0, $this->storage->size('any.file'));
        $this->assertSame(0, $this->storage->lastModified('any.file'));
    }

    public function testListContentsReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->storage->listContents());
        $this->assertSame([], $this->storage->listContents('nested/dir'));
    }
}
