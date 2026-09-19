<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Filesystem;

use Nqphp\Core\Filesystem\InMemoryStorage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InMemoryStorageTest extends TestCase
{
    private InMemoryStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new InMemoryStorage();
    }

    public function testWriteAndRead(): void
    {
        $this->storage->write('documents/test.txt', 'hello world');

        $this->assertTrue($this->storage->has('documents/test.txt'));
        $this->assertSame('hello world', $this->storage->read('documents/test.txt'));
        $this->assertSame(11, $this->storage->size('documents/test.txt'));
        $this->assertGreaterThan(0, $this->storage->lastModified('documents/test.txt'));
    }

    public function testReadNonExistentThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found: missing.txt');

        $this->storage->read('missing.txt');
    }

    public function testDelete(): void
    {
        $this->storage->write('temp.log', 'trace data');
        $this->assertTrue($this->storage->has('temp.log'));

        $this->assertTrue($this->storage->delete('temp.log'));
        $this->assertFalse($this->storage->has('temp.log'));
        $this->assertFalse($this->storage->delete('temp.log'));
    }

    public function testListContents(): void
    {
        $this->storage->write('uploads/photos/cat.jpg', 'binary1');
        $this->storage->write('uploads/photos/dog.jpg', 'binary2');
        $this->storage->write('uploads/docs/spec.pdf', 'binary3');
        $this->storage->write('root.txt', 'root');

        $rootContents = $this->storage->listContents('');
        $this->assertContains('root.txt', $rootContents);
        $this->assertContains('uploads', $rootContents);

        $photos = $this->storage->listContents('uploads/photos');
        $this->assertSame(['uploads/photos/cat.jpg', 'uploads/photos/dog.jpg'], $photos);
    }

    public function testClear(): void
    {
        $this->storage->write('file1.txt', '1');
        $this->storage->write('file2.txt', '2');

        $this->storage->clear();

        $this->assertFalse($this->storage->has('file1.txt'));
        $this->assertFalse($this->storage->has('file2.txt'));
        $this->assertSame([], $this->storage->listContents(''));
    }
}
