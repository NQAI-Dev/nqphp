<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Filesystem;

use InvalidArgumentException;
use Nqphp\Core\Filesystem\InMemoryStorage;
use Nqphp\Core\Filesystem\MemoryLimitStorage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MemoryLimitStorageTest extends TestCase
{
    public function testRejectsNegativeQuota(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MemoryLimitStorage(new InMemoryStorage(), -1);
    }

    public function testStoresWithinQuota(): void
    {
        $inner = new InMemoryStorage();
        $storage = new MemoryLimitStorage($inner, 100);

        $storage->write('file1.txt', 'hello');
        $this->assertSame('hello', $storage->read('file1.txt'));
        $this->assertSame(5, $storage->getTotalBytesUsed());
        $this->assertSame(95, $storage->getAvailableBytes());
        $this->assertSame(100, $storage->getMaxBytes());
    }

    public function testOverwritesExistingFileCalculatingDelta(): void
    {
        $inner = new InMemoryStorage();
        $storage = new MemoryLimitStorage($inner, 10);

        $storage->write('file1.txt', '12345'); // 5 bytes
        $storage->write('file1.txt', '1234567890'); // overwrite with 10 bytes -> fits
        $this->assertSame(10, $storage->getTotalBytesUsed());

        $this->expectException(RuntimeException::class);
        $storage->write('file1.txt', '12345678901'); // 11 bytes -> exceeds
    }

    public function testThrowsWhenExceedingQuota(): void
    {
        $inner = new InMemoryStorage();
        $storage = new MemoryLimitStorage($inner, 10);

        $storage->write('a.txt', '12345');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Storage quota exceeded');
        $storage->write('b.txt', '123456');
    }

    public function testProxyMethodsWorkCorrectly(): void
    {
        $inner = new InMemoryStorage();
        $storage = new MemoryLimitStorage($inner, 50);

        $storage->write('doc.txt', 'content');
        $this->assertTrue($storage->has('doc.txt'));
        $this->assertSame(7, $storage->size('doc.txt'));
        $this->assertGreaterThan(0, $storage->lastModified('doc.txt'));
        $this->assertContains('doc.txt', $storage->listContents());

        $this->assertTrue($storage->delete('doc.txt'));
        $this->assertFalse($storage->has('doc.txt'));
        $this->assertSame(0, $storage->getTotalBytesUsed());
        $this->assertSame(50, $storage->getAvailableBytes());
    }
}
