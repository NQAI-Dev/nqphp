<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Scheduler\ScheduleDiscoverer;
use Nqphp\Core\Scheduler\ScheduleListCommand;
use Nqphp\Core\Scheduler\ScheduleRunCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for the framework-side scheduler introspection + runner
 * commands (ScheduleListCommand + ScheduleRunCommand).
 *
 * Each test stands up an isolated temp project tree containing
 * feature-scheduled classes, points ScheduleDiscoverer at it, and
 * exercises one or both commands through CommandTester. No
 * singleton global state, no kernel boot — the commands accept the
 * discoverer as a constructor dep, so the test harness can wire a
 * scratch discoverer directly.
 */
final class SchedulerCommandsTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/nqphp-sched-cmd-' . uniqid('', true);
        mkdir($this->tmpDir . '/Feature/Foo/Scheduler', 0755, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tmpDir)) {
            return;
        }
        // Best-effort cleanup; ignore failures so a single stuck
        // chmod 0444 file does not cascade into the next test.
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($rii as $f) {
            @chmod((string) $f->getPathname(), 0755);
            if ($f->isDir()) {
                @rmdir((string) $f->getPathname());
            } else {
                @unlink((string) $f->getPathname());
            }
        }
        @rmdir($this->tmpDir);
    }

    // ---- helpers ---------------------------------------------------

    private function writeClass(string $relPath, string $body): void
    {
        $full = $this->tmpDir . '/' . ltrim($relPath, '/');
        $dir = dirname($full);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($full, $body);
    }

    private function newDiscoverer(): ScheduleDiscoverer
    {
        return new ScheduleDiscoverer([$this->tmpDir]);
    }

    /** @return array{0: Application, 1: CommandTester} */
    private function wire(ScheduleDiscoverer $discoverer, string $name): array
    {
        $app = new Application('nqphp-test');
        $cmdClass = ($name === 'schedule:list')
            ? ScheduleListCommand::class
            : ScheduleRunCommand::class;
        $ref = new \ReflectionClass($cmdClass);
        /** @var \Symfony\Component\Console\Command\Command $cmd */
        $cmd = $ref->newInstanceWithoutConstructor();
        $cmd->__construct($discoverer);
        $cmd->setName($name);
        $app->add($cmd);
        return [$app, new CommandTester($app->find($name))];
    }

    // ---- ScheduleListCommand --------------------------------------

    public function testListRendersHeaderAndColumns(): void
    {
        $this->writeClass('Feature/Foo/Scheduler/Foo.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Foo
{
    #[Schedule(cron: '0 * * * *', description: 'Top of every hour.')]
    public static function topOfHour(): void {}
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:list');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertStringContainsString('Discovered schedules', $out);
        self::assertStringContainsString('NAME', $out);
        self::assertStringContainsString('CRON', $out);
        self::assertStringContainsString('NEXT RUN', $out);
        self::assertStringContainsString('DESCRIPTION', $out);
        self::assertStringContainsString('App\Foo\Scheduler\Foo::topOfHour', $out);
        self::assertStringContainsString('0 * * * *', $out);
        self::assertStringContainsString('Top of every hour.', $out);
        self::assertSame(0, $tester->getStatusCode());
    }

    public function testListReportsInvalidCron(): void
    {
        $this->writeClass('Feature/Foo/Scheduler/Bar.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Bar
{
    // 60 is not a valid minute field — dragonmantank/cron-expression
    // rejects it during construction.
    #[Schedule(cron: '60 * * * *', description: 'Bogus minute.')]
    public static function bogus(): void {}
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:list');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertStringContainsString('App\Foo\Scheduler\Bar::bogus', $out);
        self::assertStringContainsString('(invalid)', $out);
        self::assertSame(0, $tester->getStatusCode());
    }

    public function testListRawEmitsTsv(): void
    {
        $this->writeClass('Feature/Foo/Scheduler/Baz.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Baz
{
    #[Schedule(cron: '@daily', description: 'Daily clean-up.', name: 'clean:daily')]
    public static function clean(): void {}
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:list');
        $tester->execute(['--raw' => true]);
        $out = $tester->getDisplay();
        $lines = array_values(array_filter(explode("\n", $out), static fn ($l) => $l !== ''));

        // Header + one data row, no borders, tab-separated.
        self::assertCount(2, $lines);
        self::assertSame("NAME\tCRON\tNEXT RUN\tDESCRIPTION", $lines[0]);
        $cells = explode("\t", $lines[1]);
        self::assertSame('clean:daily', $cells[0]);
        self::assertSame('@daily', $cells[1]);
        self::assertSame('Daily clean-up.', $cells[3]);
        // NEXT RUN must parse as Y-m-d H:i TZ.
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2} /', $cells[2]);
        self::assertSame(0, $tester->getStatusCode());
    }

    public function testListEmptyReturnsSuccess(): void
    {
        // No scheduler files written — just an empty Feature dir.
        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:list');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertStringContainsString('(no schedules', $out);
        self::assertSame(0, $tester->getStatusCode());
    }

    public function testListHonoursExplicitNameAndAlias(): void
    {
        $this->writeClass('Feature/Foo/Scheduler/Named.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Named
{
    #[Schedule(cron: '@hourly', name: 'cache:warm', description: 'Warm the cache hourly.')]
    public static function warm(): void {}
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:list');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertStringContainsString('cache:warm', $out);
        self::assertStringNotContainsString('Named::warm', $out);
        self::assertSame(0, $tester->getStatusCode());
    }

    // ---- ScheduleRunCommand ---------------------------------------

    public function testRunInvokesDueSchedule(): void
    {
        $markerFile = $this->tmpDir . '/ran-once-' . bin2hex(random_bytes(4));
        $this->writeClass('Feature/Foo/Scheduler/Runs.php', <<<PHP
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Runs
{
    #[Schedule(cron: '* * * * *', description: 'Every minute — always due during the test.')]
    public static function fire(): void
    {
        file_put_contents('{$markerFile}', 'fired');
    }
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertFileExists($markerFile);
        self::assertSame('fired', (string) file_get_contents($markerFile));
        self::assertStringContainsString('Running', $out);
        self::assertStringContainsString('App\\Foo\\Scheduler\\Runs::fire', $out);
        self::assertSame(0, $tester->getStatusCode());

        @unlink($markerFile);
    }

    public function testRunSkipsNotDue(): void
    {
        $markerFile = $this->tmpDir . '/should-not-exist';
        $this->writeClass('Feature/Foo/Scheduler/Quarterly.php', <<<PHP
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Quarterly
{
    // 31st of February does not exist — CronExpression rejects every wall-clock time,
    // so isDue() is always false.
    #[Schedule(cron: '0 0 31 2 *', description: 'Feb 31st — never due.')]
    public static function never(): void
    {
        file_put_contents('{$markerFile}', 'fired');
    }
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertFileDoesNotExist($markerFile);
        self::assertStringContainsString('skipped=', $out);
        self::assertSame(0, $tester->getStatusCode());
    }

    public function testRunForceInvokesAll(): void
    {
        $counterFile = $this->tmpDir . '/counter';
        $this->writeClass('Feature/Foo/Scheduler/Counted.php', <<<PHP
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Counted
{
    #[Schedule(cron: '0 0 31 2 *', description: 'Feb 31st — never due under cron, force runs it.')]
    public static function tick(): void
    {
        \$n = (int) (file_exists('{$counterFile}') ? file_get_contents('{$counterFile}') : 0);
        file_put_contents('{$counterFile}', (string) (\$n + 1));
    }
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute(['--force' => true]);
        $out = $tester->getDisplay();

        self::assertFileExists($counterFile);
        self::assertSame('1', (string) file_get_contents($counterFile));
        self::assertStringContainsString('force=yes', $out);
        self::assertSame(0, $tester->getStatusCode());

        @unlink($counterFile);
    }

    public function testRunDryRunDoesNotInvoke(): void
    {
        $markerFile = $this->tmpDir . '/should-not-exist';
        $this->writeClass('Feature/Foo/Scheduler/Maybe.php', <<<PHP
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Maybe
{
    #[Schedule(cron: '* * * * *', description: 'Always due; dry-run must skip invocation.')]
    public static function maybe(): void
    {
        file_put_contents('{$markerFile}', 'fired');
    }
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute(['--dry-run' => true]);
        $out = $tester->getDisplay();

        self::assertFileDoesNotExist($markerFile);
        self::assertStringContainsString('[dry-run] would run', $out);
        self::assertStringContainsString('dry-run=yes', $out);
        self::assertSame(0, $tester->getStatusCode());
    }

    public function testRunReturnsFailureOnCallableError(): void
    {
        $this->writeClass('Feature/Foo/Scheduler/Broken.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Broken
{
    #[Schedule(cron: '* * * * *', description: 'Always due; throws on invocation.')]
    public static function kaboom(): void
    {
        throw new \RuntimeException('kaboom!');
    }
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertStringContainsString('kaboom!', $out);
        self::assertStringContainsString('errors=1', $out);
        self::assertSame(1, $tester->getStatusCode());
    }

    public function testRunReturnsTwoOnInvalidCronOnly(): void
    {
        $this->writeClass('Feature/Foo/Scheduler/Bad.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Bad
{
    #[Schedule(cron: '60 * * * *', description: 'Invalid minute field.')]
    public static function bad(): void {}
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertStringContainsString('invalid cron', $out);
        self::assertSame(2, $tester->getStatusCode());
    }

    public function testRunEmptyReturnsSuccess(): void
    {
        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertStringContainsString('(no schedules', $out);
        self::assertSame(0, $tester->getStatusCode());
    }

    public function testRunLockPreventsSecondInvocation(): void
    {
        // The second invocation must silently no-op (return 0) when
        // the first holds the lock. We model "first holds the lock"
        // by acquiring LOCK_EX outside the command and never releasing
        // it within the test — the command's LOCK_EX|LOCK_NB must
        // fail and the command must exit 0.
        $lockPath = $this->tmpDir . '/schedule.lock';
        $lock = fopen($lockPath, 'c');
        self::assertNotFalse($lock);
        self::assertTrue(flock($lock, LOCK_EX | LOCK_NB));

        $this->writeClass('Feature/Foo/Scheduler/Any.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Any
{
    #[Schedule(cron: '* * * * *', description: 'Always due — must NOT fire under lock.')]
    public static function any(): void
    {
        // Intentionally empty; the assertion is that the command
        // exits before reaching here.
        throw new \RuntimeException('must not run under lock');
    }
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute(['--lock' => $lockPath]);
        $out = $tester->getDisplay();

        self::assertStringContainsString('Lock held', $out);
        self::assertSame(0, $tester->getStatusCode());

        flock($lock, LOCK_UN);
        fclose($lock);
    }

    public function testRunNowOverrideControlsDue(): void
    {
        // Feb 31st never exists — passing --now that points inside Feb
        // does not change that. Conversely, passing --now='2024-02-29
        // 00:00:00' on a '* * * * *' expression must fire.
        $marker = $this->tmpDir . '/now-fired';
        $this->writeClass('Feature/Foo/Scheduler/TimeTravel.php', <<<PHP
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class TimeTravel
{
    #[Schedule(cron: '0 0 29 2 *', description: 'Feb 29th — leap-day only.')]
    public static function leap(): void
    {
        file_put_contents('{$marker}', 'leap');
    }
}
PHP);

        // 2024 IS a leap year — Feb 29th 00:00 satisfies the cron.
        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute(['--now' => '2024-02-29 00:00:00']);
        self::assertFileExists($marker);
        @unlink($marker);

        // 2025 is not — Feb 29th does not exist on the wall clock.
        $marker2 = $this->tmpDir . '/now-not-fired';
        $this->writeClass('Feature/Foo/Scheduler/Skip.php', <<<PHP
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Skip
{
    #[Schedule(cron: '0 0 29 2 *', description: 'Feb 29th — must NOT fire on 2025.')]
    public static function skip(): void
    {
        file_put_contents('{$marker2}', 'no');
    }
}
PHP);

        [, $tester2] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester2->execute(['--now' => '2025-02-28 12:00:00']);
        self::assertFileDoesNotExist($marker2);
    }

    public function testRunMixedSuccessAndFailureReportsCorrectCounts(): void
    {
        $marker = $this->tmpDir . '/ok';
        $this->writeClass('Feature/Foo/Scheduler/Ok.php', <<<PHP
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Ok
{
    #[Schedule(cron: '* * * * *', description: 'Always due — must succeed.')]
    public static function ok(): void
    {
        file_put_contents('{$marker}', 'ok');
    }
}
PHP);
        $this->writeClass('Feature/Foo/Scheduler/Fail.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Foo\Scheduler;

use Nqphp\Core\Attribute\Schedule;

final class Fail
{
    #[Schedule(cron: '* * * * *', description: 'Always due — must throw.')]
    public static function fail(): void
    {
        throw new \RuntimeException('boom');
    }
}
PHP);

        [, $tester] = $this->wire($this->newDiscoverer(), 'schedule:run');
        $tester->execute([]);
        $out = $tester->getDisplay();

        self::assertFileExists($marker);
        self::assertSame('ok', (string) file_get_contents($marker));
        self::assertStringContainsString('boom', $out);
        self::assertStringContainsString('ran=1', $out);
        self::assertStringContainsString('errors=1', $out);
        // One runnable failed → exit 1.
        self::assertSame(1, $tester->getStatusCode());

        @unlink($marker);
    }
}
