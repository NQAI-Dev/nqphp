<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

/**
 * An event subscriber registers multiple listeners via getSubscribedEvents().
 *
 * Usage example:
 *
 *   class UserEventSubscriber implements EventSubscriberInterface
 *   {
 *       public static function getSubscribedEvents(): array
 *       {
 *           return [
 *               UserCreatedEvent::class => 'onUserCreated',
 *               UserDeletedEvent::class => ['onUserDeleted', 10],  // [method, priority]
 *           ];
 *       }
 *
 *       public function onUserCreated(UserCreatedEvent $event): void { ... }
 *       public function onUserDeleted(UserDeletedEvent $event): void { ... }
 *   }
 */
interface EventSubscriberInterface
{
    /**
     * Return a map of event class → listener method name, or [method, priority].
     *
     * Priority is an integer; higher = runs earlier. Default priority = 0.
     *
     * @return array<class-string, string|array{0: string, 1?: int}>
     */
    public static function getSubscribedEvents(): array;
}
