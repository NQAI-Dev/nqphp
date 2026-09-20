<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\QueueWorkCommand;
use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Queue\DatabaseQueue;
use Nqphp\Core\Queue\JobInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TestQueueWorkJob implements JobInterface
{
    public static bool $handled = false;

    public function handle(): void
    {
        self::$handled = true;
    }

    public function getMaxTries(): int
    {
        return 3;
    }

    public function getRetryDelay(): int
    {
        return 0;
    }
}

class QueueWorkCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_queue_cmd_test_' . uniqid();
        @mkdir($this->tempDir . '/config', 0777, true);

        $config = "database:\n  driver: 'sqlite'\n  database: ':memory:'\n";
        file_put_contents($this->tempDir . '/config/database.yaml', $config);

        TestQueueWorkJob::$handled = false;
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

    public function testExecuteOnceWithEmptyQueue(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new QueueWorkCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--once' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Processing single job...', $output);
        $this->assertStringContainsString('No jobs to process.', $output);
    }

    public function testExecuteOnceProcessesJobSuccessfully(): void
    {
        $kernel = new Kernel($this->tempDir);
        $pdo = $kernel->pdo();

        $queue = new DatabaseQueue($pdo);
        $queue->push(new TestQueueWorkJob());

        $command = new QueueWorkCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--once' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Processing single job...', $output);
        $this->assertStringContainsString('Job executed.', $output);
        $this->assertTrue(TestQueueWorkJob::$handled);
    }
}
