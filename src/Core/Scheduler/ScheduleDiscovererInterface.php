<?php

declare(strict_types=1);

namespace Nqphp\Core\Scheduler;

/**
 * Contract for discovering scheduled tasks.
 */
interface ScheduleDiscovererInterface
{
    /**
     * Discover schedules (chainable).
     */
    public function discover(): self;

    /**
     * @return list<array{name: string, cron: string, description: string, callable: callable}>
     */
    public function all(): array;
}
