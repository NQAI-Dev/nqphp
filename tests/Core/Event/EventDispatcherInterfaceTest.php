<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Event;

use Nqphp\Core\Event\Event;
use Nqphp\Core\Event\EventDispatcher;
use Nqphp\Core\Event\EventDispatcherInterface;
use PHPUnit\Framework\TestCase;

class TestCustomEvent extends Event
{
    public bool $handled = false;
}

class EventDispatcherInterfaceTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $dispatcher = new EventDispatcher();
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    public function testDispatchAndListenerContract(): void
    {
        $dispatcher = new EventDispatcher();
        $called = false;

        $dispatcher->listen(TestCustomEvent::class, function (TestCustomEvent $e) use (&$called) {
            $called = true;
            $e->handled = true;
        });

        $this->assertCount(1, $dispatcher->getListeners(TestCustomEvent::class));
        $this->assertSame([TestCustomEvent::class], $dispatcher->getRegisteredEvents());

        $event = new TestCustomEvent();
        $dispatcher->dispatch($event);

        $this->assertTrue($called);
        $this->assertTrue($event->handled);

        $dispatcher->removeListeners(TestCustomEvent::class);
        $this->assertSame([], $dispatcher->getListeners(TestCustomEvent::class));
    }
}
