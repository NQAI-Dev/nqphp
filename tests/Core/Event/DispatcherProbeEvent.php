<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Event;

use Nqphp\Core\Event\Event;

/**
 * Shared probe event for discoverer tests (listener fixtures type-
 * hint this class).
 */
final class DispatcherProbeEvent extends Event
{
    /** @var list<string> */
    public array $notes = [];
}
