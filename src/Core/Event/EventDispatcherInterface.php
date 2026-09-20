<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

/**
 * Contract for dispatching events and managing event listeners.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event to all registered listeners.
     */
    public function dispatch(EventInterface $event): void;

    /**
     * Subscribe a listener to an event class.
     *
     * @param class-string $eventClass
     * @param callable $listener
     * @param int $priority Higher runs first. Default: 0.
     */
    public function listen(string $eventClass, callable $listener, int $priority = 0): void;

    /**
     * Register all listeners declared by an EventSubscriberInterface.
     */
    public function addSubscriber(EventSubscriberInterface $subscriber): void;

    /**
     * Return all registered listeners for an event class, sorted by priority.
     *
     * @param class-string $eventClass
     * @return array<callable>
     */
    public function getListeners(string $eventClass): array;

    /**
     * Remove all listeners for a given event class.
     *
     * @param class-string $eventClass
     */
    public function removeListeners(string $eventClass): void;

    /**
     * Return all registered event classes with listeners.
     *
     * @return list<string>
     */
    public function getRegisteredEvents(): array;
}
