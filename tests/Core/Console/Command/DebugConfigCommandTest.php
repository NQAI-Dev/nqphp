<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Config\Repository;
use Nqphp\Core\Console\Command\DebugConfigCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DebugConfigCommandTest extends TestCase
{
    public function testExecuteOutputsAllConfiguration(): void
    {
        $repo = new Repository([
            'app' => [
                'name' => 'nqphp',
                'debug' => true,
            ],
            'database' => [
                'default' => 'sqlite',
            ],
        ]);

        $command = new DebugConfigCommand($repo);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Всего параметров конфигурации: 3', $output);
        $this->assertStringContainsString('app.name', $output);
        $this->assertStringContainsString('nqphp', $output);
        $this->assertStringContainsString('app.debug', $output);
        $this->assertStringContainsString('true', $output);
        $this->assertStringContainsString('database.default', $output);
        $this->assertStringContainsString('sqlite', $output);
    }

    public function testExecuteWithSpecificExistingKey(): void
    {
        $repo = new Repository([
            'services' => [
                'mail' => [
                    'host' => 'smtp.example.com',
                    'port' => 587,
                ],
            ],
        ]);

        $command = new DebugConfigCommand($repo);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['key' => 'services.mail']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Конфигурация для "services.mail":', $output);
        $this->assertStringContainsString('services.mail.host', $output);
        $this->assertStringContainsString('smtp.example.com', $output);
        $this->assertStringContainsString('services.mail.port', $output);
        $this->assertStringContainsString('587', $output);
    }

    public function testExecuteWithScalarKey(): void
    {
        $repo = new Repository([
            'app' => [
                'env' => 'production',
            ],
        ]);

        $command = new DebugConfigCommand($repo);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['key' => 'app.env']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('app.env = production', $output);
    }

    public function testExecuteWithNonExistentKeyReturnsFailure(): void
    {
        $repo = new Repository(['app' => ['name' => 'test']]);
        $command = new DebugConfigCommand($repo);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['key' => 'nonexistent.key']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Ключ конфигурации "nonexistent.key" не найден.', $output);
    }

    public function testExecuteWhenRepositoryIsEmpty(): void
    {
        $repo = new Repository([]);
        $command = new DebugConfigCommand($repo);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Конфигурация пуста.', $output);
    }
}
