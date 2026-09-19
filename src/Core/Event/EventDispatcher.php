<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

/**
 * Event dispatcher for synchronous pub/sub inside the core.
 */
class EventDispatcher
{
    /**
     * @var array<string, array<callable>>
     */
    private array $listeners = [];

    /**
     * Subscribe a listener to an event class.
     *
     * @param class-string $eventClass
     * @param callable $listener
     */
    public function listen(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * Dispatch an event to all registered listeners.
     */
    public function dispatch(EventInterface $event): void
    {
        $eventClass = get_class($event);

        if (!isset($this->listeners[$eventClass])) {
            return;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }
    }
}
