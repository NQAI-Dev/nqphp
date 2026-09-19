<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

/**
 * Interface for all events dispatched through the EventDispatcher.
 */
interface EventInterface
{
    /**
     * Stop the propagation of this event to further listeners.
     */
    public function stopPropagation(): void;

    /**
     * Check whether propagation was stopped.
     */
    public function isPropagationStopped(): bool;
}
