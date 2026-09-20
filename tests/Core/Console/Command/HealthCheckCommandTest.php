<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\HealthCheckCommand;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class HealthCheckCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_health_cmd_' . uniqid();
        @mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        @rmdir($this->tempDir);
    }

    public function testExecuteSuccessfulHealthCheck(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new HealthCheckCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Running system health checks...', $output);
        $this->assertStringContainsString('[OK]', $output);
        $this->assertStringContainsString('disk_space', $output);
        $this->assertStringContainsString('Overall status: OK', $output);
    }
}
