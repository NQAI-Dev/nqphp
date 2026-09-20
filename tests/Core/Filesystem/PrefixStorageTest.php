<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Filesystem;

use Nqphp\Core\Filesystem\InMemoryStorage;
use Nqphp\Core\Filesystem\PrefixStorage;
use Nqphp\Core\Filesystem\StorageInterface;
use PHPUnit\Framework\TestCase;

class PrefixStorageTest extends TestCase
{
    private InMemoryStorage $underlying;
    private PrefixStorage $storage;

    protected function setUp(): void
    {
        $this->underlying = new InMemoryStorage();
        $this->storage = new PrefixStorage($this->underlying, 'uploads/media');
    }

    public function testImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(StorageInterface::class, $this->storage);
    }

    public function testWriteAndReadWithPrefix(): void
    {
        $this->storage->write('avatar.png', 'image-bytes');

        $this->assertTrue($this->storage->has('avatar.png'));
        $this->assertSame('image-bytes', $this->storage->read('avatar.png'));

        // Underlying storage contains prefixed key
        $this->assertTrue($this->underlying->has('uploads/media/avatar.png'));
        $this->assertSame('image-bytes', $this->underlying->read('uploads/media/avatar.png'));
    }

    public function testDeleteWithPrefix(): void
    {
        $this->storage->write('doc.pdf', 'pdf-bytes');
        $this->assertTrue($this->storage->has('doc.pdf'));

        $this->assertTrue($this->storage->delete('doc.pdf'));
        $this->assertFalse($this->storage->has('doc.pdf'));
        $this->assertFalse($this->underlying->has('uploads/media/doc.pdf'));
    }

    public function testSizeAndLastModified(): void
    {
        $this->storage->write('hello.txt', 'hello world');

        $this->assertSame(11, $this->storage->size('hello.txt'));
        $this->assertGreaterThan(0, $this->storage->lastModified('hello.txt'));
    }

    public function testListContentsStripsPrefix(): void
    {
        $this->storage->write('img1.png', '1');
        $this->storage->write('img2.png', '2');
        $this->underlying->write('other/unrelated.txt', '3');

        $contents = $this->storage->listContents();

        $this->assertContains('img1.png', $contents);
        $this->assertContains('img2.png', $contents);
        $this->assertNotContains('other/unrelated.txt', $contents);
        $this->assertNotContains('uploads/media/img1.png', $contents);
    }

    public function testListContentsInSubdirectory(): void
    {
        $this->storage->write('docs/report.pdf', 'report');

        $contents = $this->storage->listContents('docs');
        $this->assertContains('docs/report.pdf', $contents);
    }
}
