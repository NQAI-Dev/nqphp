<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\DebugEventsCommand;
use Nqphp\Core\Event\Event;
use Nqphp\Core\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CustomDummyEvent extends Event
{
}

class AnotherCustomEvent extends Event
{
}

class DummySubscriber
{
    public function handle(CustomDummyEvent $event): void
    {
    }
}

class DebugEventsCommandTest extends TestCase
{
    public function testRendersAllRegisteredEvents(): void
    {
        $dispatcher = new EventDispatcher();
        $subscriber = new DummySubscriber();

        $dispatcher->listen(CustomDummyEvent::class, [$subscriber, 'handle'], 10);
        $dispatcher->listen(CustomDummyEvent::class, function (CustomDummyEvent $event): void {
        }, 0);
        $dispatcher->listen(AnotherCustomEvent::class, fn () => null);

        $command = new DebugEventsCommand($dispatcher);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Event', $output);
        $this->assertStringContainsString(CustomDummyEvent::class, $output);
        $this->assertStringContainsString(AnotherCustomEvent::class, $output);
        $this->assertStringContainsString('DummySubscriber::handle', $output);
        $this->assertStringContainsString('Closure', $output);
    }

    public function testFiltersByEventName(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->listen(CustomDummyEvent::class, fn () => null);
        $dispatcher->listen(AnotherCustomEvent::class, fn () => null);

        $command = new DebugEventsCommand($dispatcher);
        $tester = new CommandTester($command);

        $tester->execute(['--event' => 'AnotherCustom']);

        $output = $tester->getDisplay();

        $this->assertStringNotContainsString(CustomDummyEvent::class, $output);
        $this->assertStringContainsString(AnotherCustomEvent::class, $output);
    }

    public function testOutputsMessageWhenNoEventsMatch(): void
    {
        $dispatcher = new EventDispatcher();
        $command = new DebugEventsCommand($dispatcher);
        $tester = new CommandTester($command);

        $tester->execute(['--event' => 'NonExistent']);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('События не найдены.', $output);
    }
}
