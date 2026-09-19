<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Event;

use Nqphp\Core\Event\Event;
use Nqphp\Core\Event\EventDispatcher;
use Nqphp\Core\Event\EventInterface;
use Nqphp\Core\Event\EventSubscriberInterface;
use PHPUnit\Framework\TestCase;

// ─── Fixtures ────────────────────────────────────────────────────────────────

final class SampleEvent extends Event
{
    public array $trace = [];
}

final class OrderedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SampleEvent::class => [
                ['runFirst',  10],
                ['runSecond',  5],
                ['runThird',   0],
            ],
        ];
    }

    public function runFirst(SampleEvent $e): void  { $e->trace[] = 'first';  }
    public function runSecond(SampleEvent $e): void { $e->trace[] = 'second'; }
    public function runThird(SampleEvent $e): void  { $e->trace[] = 'third';  }
}

final class StopPropagationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SampleEvent::class => 'onEvent',
        ];
    }

    public function onEvent(SampleEvent $e): void
    {
        $e->trace[] = 'stopper';
        $e->stopPropagation();
    }
}

// ─── Test case ───────────────────────────────────────────────────────────────

final class EventDispatcherTest extends TestCase
{
    public function testListenAndDispatch(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new SampleEvent();

        $dispatcher->listen(SampleEvent::class, fn (SampleEvent $e) => ($e->trace[] = 'a'));
        $dispatcher->listen(SampleEvent::class, fn (SampleEvent $e) => ($e->trace[] = 'b'));

        $dispatcher->dispatch($event);

        $this->assertSame(['a', 'b'], $event->trace);
    }

    public function testPriorityOrderingViaListen(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new SampleEvent();

        $dispatcher->listen(SampleEvent::class, fn (SampleEvent $e) => ($e->trace[] = 'low'),  0);
        $dispatcher->listen(SampleEvent::class, fn (SampleEvent $e) => ($e->trace[] = 'high'), 10);
        $dispatcher->listen(SampleEvent::class, fn (SampleEvent $e) => ($e->trace[] = 'mid'),  5);

        $dispatcher->dispatch($event);

        $this->assertSame(['high', 'mid', 'low'], $event->trace);
    }

    public function testAddSubscriberRegistersAllListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new SampleEvent();

        $dispatcher->addSubscriber(new OrderedSubscriber());
        $dispatcher->dispatch($event);

        $this->assertSame(['first', 'second', 'third'], $event->trace);
    }

    public function testSubscriberWithStringSpec(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new SampleEvent();

        $subscriber = new class implements EventSubscriberInterface {
            public array $calls = [];
            public static function getSubscribedEvents(): array
            {
                return [SampleEvent::class => 'handle'];
            }
            public function handle(SampleEvent $e): void { $e->trace[] = 'subscriber-string-spec'; }
        };

        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($event);

        $this->assertSame(['subscriber-string-spec'], $event->trace);
    }

    public function testStopPropagationHaltsChain(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new SampleEvent();

        $dispatcher->addSubscriber(new StopPropagationSubscriber());
        $dispatcher->listen(SampleEvent::class, fn (SampleEvent $e) => ($e->trace[] = 'after-stopper'));

        $dispatcher->dispatch($event);

        $this->assertSame(['stopper'], $event->trace);
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testDispatchNoopOnNoListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new SampleEvent();

        $dispatcher->dispatch($event);

        $this->assertSame([], $event->trace);
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testGetListenersSortedByPriority(): void
    {
        $dispatcher = new EventDispatcher();

        $a = fn (EventInterface $e) => null;
        $b = fn (EventInterface $e) => null;
        $c = fn (EventInterface $e) => null;

        $dispatcher->listen(SampleEvent::class, $a, 0);
        $dispatcher->listen(SampleEvent::class, $b, 20);
        $dispatcher->listen(SampleEvent::class, $c, 10);

        $listeners = $dispatcher->getListeners(SampleEvent::class);

        $this->assertSame([$b, $c, $a], $listeners);
    }

    public function testRemoveListenersClearsEvent(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new SampleEvent();

        $dispatcher->listen(SampleEvent::class, fn (SampleEvent $e) => ($e->trace[] = 'x'));
        $dispatcher->removeListeners(SampleEvent::class);
        $dispatcher->dispatch($event);

        $this->assertSame([], $event->trace);
    }

    public function testMixedSubscriberAndDirectListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new SampleEvent();

        $dispatcher->listen(SampleEvent::class, fn (SampleEvent $e) => ($e->trace[] = 'direct-low'), -5);
        $dispatcher->addSubscriber(new OrderedSubscriber()); // priorities 10, 5, 0
        $dispatcher->listen(SampleEvent::class, fn (SampleEvent $e) => ($e->trace[] = 'direct-high'), 15);

        $dispatcher->dispatch($event);

        $this->assertSame(['direct-high', 'first', 'second', 'third', 'direct-low'], $event->trace);
    }
}
