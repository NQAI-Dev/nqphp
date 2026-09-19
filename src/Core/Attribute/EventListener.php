<?php

declare(strict_types=1);

namespace Nqphp\Core\Attribute;

/**
 * Mark a class method as an event listener, auto-discovered by the
 * Kernel and subscribed to the EventDispatcher at boot.
 *
 * The listening method must accept exactly one argument typed with
 * the event class it handles:
 *
 *   #[EventListener]
 *   public function onRequest(KernelRequestEvent $event): void { ... }
 *
 * Listeners run in registration order (discovery order); call
 * $event->stopPropagation() to skip the remaining listeners.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class EventListener
{
    /**
     * @param int $priority Higher runs first within the same event (default 0).
     */
    public function __construct(
        public readonly int $priority = 0,
    ) {
    }
}
