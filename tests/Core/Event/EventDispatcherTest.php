<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Event;

use Nqphp\Core\Event\Event;
use Nqphp\Core\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;

class DummyEvent extends Event
{
    public int $counter = 0;
}

class AnotherEvent extends Event
{
}

class EventDispatcherTest extends TestCase
{
    public function testDispatchCallsListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new DummyEvent();

        $dispatcher->listen(DummyEvent::class, function (DummyEvent $e) {
            $e->counter++;
        });

        $dispatcher->listen(DummyEvent::class, function (DummyEvent $e) {
            $e->counter += 2;
        });

        $dispatcher->dispatch($event);

        $this->assertSame(3, $event->counter);
    }

    public function testDispatchDoesNotCallOtherEventListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new DummyEvent();

        $dispatcher->listen(AnotherEvent::class, function (AnotherEvent $e) {
            $this->fail('Listener for AnotherEvent should not be called');
        });

        $dispatcher->dispatch($event);

        $this->assertSame(0, $event->counter);
    }

    public function testStopPropagation(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new DummyEvent();

        $dispatcher->listen(DummyEvent::class, function (DummyEvent $e) {
            $e->counter++;
            $e->stopPropagation();
        });

        $dispatcher->listen(DummyEvent::class, function (DummyEvent $e) {
            $e->counter += 2;
        });

        $dispatcher->dispatch($event);

        $this->assertSame(1, $event->counter);
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testDispatchWithoutListenersDoesNotCrash(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new DummyEvent();

        $dispatcher->dispatch($event);

        $this->assertSame(0, $event->counter);
    }
}
