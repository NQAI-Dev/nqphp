<?php

namespace Nqphp\Tests\Core\Scheduler\Fixtures;

use Nqphp\Core\Attribute\Schedule;

class SampleScheduler
{
    public static int $callCount = 0;

    #[Schedule(cron: '* * * * *', description: 'Run every minute', name: 'test:every-minute')]
    public static function everyMinute(): void
    {
        self::$callCount++;
    }

    #[Schedule(cron: '0 0 * * *', description: 'Run daily', name: 'test:daily')]
    public static function daily(): void
    {
        self::$callCount++;
    }
}
