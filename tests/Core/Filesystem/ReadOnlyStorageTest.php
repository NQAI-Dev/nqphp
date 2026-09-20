<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Filesystem;

use Nqphp\Core\Filesystem\InMemoryStorage;
use Nqphp\Core\Filesystem\ReadOnlyStorage;
use Nqphp\Core\Filesystem\StorageInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ReadOnlyStorageTest extends TestCase
{
    private InMemoryStorage $inner;
    private ReadOnlyStorage $storage;

    protected function setUp(): void
    {
        $this->inner = new InMemoryStorage();
        $this->inner->write('docs/readme.txt', 'Documentation content');
        $this->storage = new ReadOnlyStorage($this->inner);
    }

    public function testImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(StorageInterface::class, $this->storage);
    }

    public function testReadOperationsWork(): void
    {
        $this->assertTrue($this->storage->has('docs/readme.txt'));
        $this->assertFalse($this->storage->has('docs/missing.txt'));
        $this->assertSame('Documentation content', $this->storage->read('docs/readme.txt'));
        $this->assertSame(strlen('Documentation content'), $this->storage->size('docs/readme.txt'));
        $this->assertGreaterThan(0, $this->storage->lastModified('docs/readme.txt'));
        $this->assertContains('docs/readme.txt', $this->storage->listContents('docs'));
    }

    public function testWriteThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cannot write to 'test.txt': storage is read-only.");

        $this->storage->write('test.txt', 'new data');
    }

    public function testDeleteThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cannot delete 'docs/readme.txt': storage is read-only.");

        $this->storage->delete('docs/readme.txt');
    }
}
