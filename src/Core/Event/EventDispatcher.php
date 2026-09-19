<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

/**
 * Event dispatcher for synchronous pub/sub inside the core.
 *
 * Supports:
 *  - Direct listener registration via listen()
 *  - Subscriber registration via addSubscriber()
 *  - Listener priority ordering (higher int = runs first)
 */
class EventDispatcher
{
    /**
     * @var array<string, array<array{callable, int}>>
     */
    private array $listeners = [];

    /**
     * @var array<string, bool> whether listeners for an event class are sorted
     */
    private array $sorted = [];

    /**
     * Subscribe a listener to an event class.
     *
     * @param class-string $eventClass
     * @param callable     $listener
     * @param int          $priority   Higher runs first. Default: 0.
     */
    public function listen(string $eventClass, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventClass][] = [$listener, $priority];
        $this->sorted[$eventClass] = false;
    }

    /**
     * Register all listeners declared by an EventSubscriberInterface.
     */
    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach (static::getSubscribedEventsFor($subscriber) as [$eventClass, $method, $priority]) {
            $this->listen($eventClass, [$subscriber, $method], $priority);
        }
    }

    /**
     * Dispatch an event to all registered listeners in priority order.
     */
    public function dispatch(EventInterface $event): void
    {
        $eventClass = get_class($event);

        if (!isset($this->listeners[$eventClass])) {
            return;
        }

        if (!($this->sorted[$eventClass] ?? true)) {
            usort(
                $this->listeners[$eventClass],
                static fn (array $a, array $b): int => $b[1] <=> $a[1]
            );
            $this->sorted[$eventClass] = true;
        }

        foreach ($this->listeners[$eventClass] as [$listener]) {
            if ($event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }
    }

    /**
     * Return all registered listeners for an event class, sorted by priority.
     *
     * @param class-string $eventClass
     * @return array<callable>
     */
    public function getListeners(string $eventClass): array
    {
        if (!isset($this->listeners[$eventClass])) {
            return [];
        }

        if (!($this->sorted[$eventClass] ?? true)) {
            usort(
                $this->listeners[$eventClass],
                static fn (array $a, array $b): int => $b[1] <=> $a[1]
            );
            $this->sorted[$eventClass] = true;
        }

        return array_column($this->listeners[$eventClass], 0);
    }

    /**
     * Remove all listeners for a given event class.
     *
     * @param class-string $eventClass
     */
    public function removeListeners(string $eventClass): void
    {
        unset($this->listeners[$eventClass], $this->sorted[$eventClass]);
    }

    /**
     * Normalize EventSubscriberInterface::getSubscribedEvents() to
     * a flat list of [ eventClass, method, priority ] tuples.
     *
     * Accepts three spec shapes per event:
     *   'methodName'                        → priority 0
     *   ['methodName', 10]                  → explicit priority
     *   [['methodA', 10], ['methodB', 5]]   → multiple listeners
     *
     * @return array<array{0: class-string, 1: string, 2: int}>
     */
    private static function getSubscribedEventsFor(EventSubscriberInterface $subscriber): array
    {
        $result = [];
        foreach ($subscriber::getSubscribedEvents() as $eventClass => $spec) {
            if (is_string($spec)) {
                // 'methodName'
                $result[] = [$eventClass, $spec, 0];
            } elseif (is_string($spec[0])) {
                // ['methodName', priority?]
                $result[] = [$eventClass, $spec[0], $spec[1] ?? 0];
            } else {
                // [['method', priority?], ['method2', priority?], ...]
                foreach ($spec as $entry) {
                    $result[] = [$eventClass, $entry[0], $entry[1] ?? 0];
                }
            }
        }
        return $result;
    }
}
