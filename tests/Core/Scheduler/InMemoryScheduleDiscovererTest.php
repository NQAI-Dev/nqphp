<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Scheduler;

use Nqphp\Core\Scheduler\InMemoryScheduleDiscoverer;
use Nqphp\Core\Scheduler\ScheduleDiscovererInterface;
use Nqphp\Core\Scheduler\ScheduleListCommand;
use Nqphp\Core\Scheduler\ScheduleRunCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InMemoryScheduleDiscovererTest extends TestCase
{
    public function testImplementsInterfaceAndManagesSchedules(): void
    {
        $discoverer = new InMemoryScheduleDiscoverer();
        $this->assertInstanceOf(ScheduleDiscovererInterface::class, $discoverer);

        $executed = false;
        $discoverer->addSchedule('test:job', '* * * * *', function () use (&$executed): void {
            $executed = true;
        }, 'Run test job');

        $schedules = $discoverer->discover()->all();
        $this->assertCount(1, $schedules);
        $this->assertSame('test:job', $schedules[0]['name']);
        $this->assertSame('* * * * *', $schedules[0]['cron']);
        $this->assertSame('Run test job', $schedules[0]['description']);

        $schedules[0]['callable']();
        $this->assertTrue($executed);
    }

    public function testWorksWithScheduleListCommand(): void
    {
        $discoverer = new InMemoryScheduleDiscoverer([
            [
                'name' => 'clean:tmp',
                'cron' => '@daily',
                'description' => 'Cleanup temporary files',
                'callable' => fn () => null,
            ],
        ]);

        $command = new ScheduleListCommand($discoverer);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $this->assertSame(0, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('clean:tmp', $output);
        $this->assertStringContainsString('@daily', $output);
    }

    public function testWorksWithScheduleRunCommand(): void
    {
        $flag = false;
        $discoverer = new InMemoryScheduleDiscoverer([
            [
                'name' => 'test:run',
                'cron' => '* * * * *',
                'description' => 'Run task',
                'callable' => function () use (&$flag): void {
                    $flag = true;
                },
            ],
        ]);

        $command = new ScheduleRunCommand($discoverer);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--force' => true]);
        $this->assertSame(0, $exitCode);
        $this->assertTrue($flag);
    }
}
