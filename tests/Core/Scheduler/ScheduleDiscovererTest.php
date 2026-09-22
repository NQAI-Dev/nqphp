<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Scheduler;

use Nqphp\Core\Scheduler\ScheduleDiscoverer;
use PHPUnit\Framework\TestCase;

class ScheduleDiscovererTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/Fixtures';
    }

    public function testDiscoversScheduledMethods(): void
    {
        $discoverer = new ScheduleDiscoverer([$this->fixturesDir]);
        $schedules = $discoverer->discover()->all();

        $this->assertCount(2, $schedules);

        $names = array_column($schedules, 'name');
        $this->assertContains('test:every-minute', $names);
        $this->assertContains('test:daily', $names);
    }

    public function testScheduleHasExpectedFields(): void
    {
        $discoverer = new ScheduleDiscoverer([$this->fixturesDir]);
        $schedules = $discoverer->discover()->all();

        $minuteSchedule = array_values(array_filter($schedules, fn ($s) => $s['name'] === 'test:every-minute'))[0];

        $this->assertSame('* * * * *', $minuteSchedule['cron']);
        $this->assertSame('Run every minute', $minuteSchedule['description']);
        $this->assertIsArray($minuteSchedule['callable']);
        $this->assertCount(2, $minuteSchedule['callable']);
    }

    public function testEmptyDirReturnsNoSchedules(): void
    {
        $discoverer = new ScheduleDiscoverer(['/tmp/nonexistent_dir_' . uniqid()]);
        $schedules = $discoverer->discover()->all();

        $this->assertSame([], $schedules);
    }

    public function testMultipleDiscoverCallsResetResults(): void
    {
        $discoverer = new ScheduleDiscoverer([$this->fixturesDir]);

        $first = $discoverer->discover()->all();
        $second = $discoverer->discover()->all();

        $this->assertCount(count($first), $second);
    }

    public function testCalledCallableIsInvokable(): void
    {
        $discoverer = new ScheduleDiscoverer([$this->fixturesDir]);
        $schedules = $discoverer->discover()->all();

        $minuteSchedule = array_values(array_filter($schedules, fn ($s) => $s['name'] === 'test:every-minute'))[0];
        [$class, $method] = $minuteSchedule['callable'];

        \Nqphp\Tests\Core\Scheduler\Fixtures\SampleScheduler::$callCount = 0;
        $class::$method();
        $this->assertSame(1, \Nqphp\Tests\Core\Scheduler\Fixtures\SampleScheduler::$callCount);
    }
}
