<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\DebugContainerCommand;
use Nqphp\Core\Container\ServiceLocator;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DebugContainerCommandTest extends TestCase
{
    public function testExecuteFailsWithoutContainer(): void
    {
        $cmd = new DebugContainerCommand(null);
        $tester = new CommandTester($cmd);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('No ServiceLocator container instance available', $tester->getDisplay());
    }

    public function testExecuteRendersServicesTable(): void
    {
        $container = new ServiceLocator();
        $container->bind('dummy.alias', stdClass::class);
        $container->bindFactory('dummy.factory', fn () => new stdClass());
        $container->prototype('dummy.proto');
        $container->make('dummy.alias'); // instantiate singleton

        $cmd = new DebugContainerCommand($container);
        $tester = new CommandTester($cmd);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();

        $this->assertStringContainsString('Service / Identifier', $display);
        $this->assertStringContainsString('dummy.alias', $display);
        $this->assertStringContainsString('alias', $display);
        $this->assertStringContainsString('dummy.factory', $display);
        $this->assertStringContainsString('factory', $display);
        $this->assertStringContainsString('dummy.proto', $display);
        $this->assertStringContainsString('prototype', $display);
    }

    public function testExecuteAppliesFilter(): void
    {
        $container = new ServiceLocator();
        $container->bind('cache.redis', stdClass::class);
        $container->bind('logger.stdout', stdClass::class);

        $cmd = new DebugContainerCommand($container);
        $tester = new CommandTester($cmd);

        $exitCode = $tester->execute(['filter' => 'redis']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();

        $this->assertStringContainsString('cache.redis', $display);
        $this->assertStringNotContainsString('logger.stdout', $display);
    }

    public function testExecuteShowsEmptyMessageWhenNoMatches(): void
    {
        $container = new ServiceLocator();
        $container->bind('service.one', stdClass::class);

        $cmd = new DebugContainerCommand($container);
        $tester = new CommandTester($cmd);

        $exitCode = $tester->execute(['filter' => 'nonexistent']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No services matching "nonexistent" found', $tester->getDisplay());
    }
}
