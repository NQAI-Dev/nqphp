<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\StorageClearCommand;
use Nqphp\Core\Filesystem\InMemoryStorage;
use Nqphp\Core\Filesystem\StorageManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class StorageClearCommandTest extends TestCase
{
    public function testClearSpecificDisk(): void
    {
        $manager = new StorageManager('local');
        $local = new InMemoryStorage();
        $local->write('a.txt', 'file a');
        $local->write('b.txt', 'file b');

        $manager->mount('local', $local);

        $command = new StorageClearCommand($manager);
        $tester = new CommandTester($command);

        $tester->execute(['--disk' => 'local']);

        $this->assertSame(0, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Clearing storage disk [local]...', $output);
        $this->assertStringContainsString('Deleted 2 file(s) from [local].', $output);
        $this->assertFalse($local->has('a.txt'));
        $this->assertFalse($local->has('b.txt'));
    }

    public function testClearAllMountedDisks(): void
    {
        $manager = new StorageManager('d1');
        $d1 = new InMemoryStorage();
        $d1->write('file1.txt', '1');
        $d2 = new InMemoryStorage();
        $d2->write('file2.txt', '2');

        $manager->mount('d1', $d1);
        $manager->mount('d2', $d2);

        $command = new StorageClearCommand($manager);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertFalse($d1->has('file1.txt'));
        $this->assertFalse($d2->has('file2.txt'));
        $output = $tester->getDisplay();
        $this->assertStringContainsString('All mounted storage disks cleared successfully.', $output);
    }

    public function testOutputsNoticeWhenNoDisksMounted(): void
    {
        $manager = new StorageManager('empty');
        $command = new StorageClearCommand($manager);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No mounted storage disks found.', $tester->getDisplay());
    }
}
