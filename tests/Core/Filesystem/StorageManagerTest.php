<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Filesystem;

use InvalidArgumentException;
use Nqphp\Core\Filesystem\InMemoryStorage;
use Nqphp\Core\Filesystem\StorageManager;
use PHPUnit\Framework\TestCase;

class StorageManagerTest extends TestCase
{
    public function testMountAndRetrieveDisk(): void
    {
        $manager = new StorageManager('local');
        $storage = new InMemoryStorage();

        $manager->mount('local', $storage);

        $this->assertTrue($manager->hasDisk('local'));
        $this->assertSame($storage, $manager->disk());
        $this->assertSame($storage, $manager->disk('local'));
        $this->assertSame(['local'], $manager->getDisks());
    }

    public function testThrowsExceptionWhenDiskNotMounted(): void
    {
        $manager = new StorageManager('local');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disk [local] is not mounted.');
        $manager->disk('local');
    }

    public function testChangeDefaultDisk(): void
    {
        $manager = new StorageManager('local');
        $s1 = new InMemoryStorage();
        $s2 = new InMemoryStorage();

        $manager->mount('local', $s1);
        $manager->mount('backup', $s2);

        $this->assertSame('local', $manager->getDefaultDisk());
        $this->assertSame($s1, $manager->disk());

        $manager->setDefaultDisk('backup');
        $this->assertSame('backup', $manager->getDefaultDisk());
        $this->assertSame($s2, $manager->disk());
    }

    public function testUnmountDisk(): void
    {
        $manager = new StorageManager('temp');
        $storage = new InMemoryStorage();

        $manager->mount('temp', $storage);
        $this->assertTrue($manager->hasDisk('temp'));

        $manager->unmount('temp');
        $this->assertFalse($manager->hasDisk('temp'));
        $this->assertSame([], $manager->getDisks());

        $this->expectException(InvalidArgumentException::class);
        $manager->disk('temp');
    }
}
