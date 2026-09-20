<?php

declare(strict_types=1);

namespace Nqphp\Core\Scheduler;

/**
 * In-memory scheduler discoverer for tests, custom programmatic setups, or isolated task runners.
 */
class InMemoryScheduleDiscoverer implements ScheduleDiscovererInterface
{
    /** @var list<array{name: string, cron: string, description: string, callable: callable}> */
    private array $schedules = [];

    /**
     * @param list<array{name: string, cron: string, description: string, callable: callable}> $schedules
     */
    public function __construct(array $schedules = [])
    {
        $this->schedules = $schedules;
    }

    public function addSchedule(string $name, string $cron, callable $callable, string $description = ''): self
    {
        $this->schedules[] = [
            'name' => $name,
            'cron' => $cron,
            'description' => $description,
            'callable' => $callable,
        ];

        return $this;
    }

    public function discover(): self
    {
        // No filesystem scan needed for in-memory discoverer
        return $this;
    }

    /**
     * @return list<array{name: string, cron: string, description: string, callable: callable}>
     */
    public function all(): array
    {
        return $this->schedules;
    }
}
