<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

/**
 * Base event class providing propagation stopping capabilities.
 */
class Event implements EventInterface
{
    private bool $propagationStopped = false;

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
