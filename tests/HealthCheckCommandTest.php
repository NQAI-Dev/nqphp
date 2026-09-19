<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Console\Command\HealthCheckCommand;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class HealthCheckCommandTest extends TestCase
{
    public function testHealthCheckCommandExecution(): void
    {
        $kernel = new Kernel(__DIR__ . '/..');

        $command = new HealthCheckCommand($kernel);
        $tester = new CommandTester($command);

        $statusCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $output = $tester->getDisplay();

        $this->assertStringContainsString('Running system health checks...', $output);
        $this->assertStringContainsString('database', $output);
        $this->assertStringContainsString('disk_space', $output);
        $this->assertStringContainsString('Overall status:', $output);
    }
}
