<?php

declare(strict_types=1);

namespace Nqphp\Core\Scheduler;

use Cron\CronExpression;
use Nqphp\Core\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * `bin/console schedule:run` — evaluate every discovered #[Schedule]
 * and invoke the callables whose cron is due now.
 *
 * Due-now semantics:
 *   The schedule's cron expression is tested with
 *   `Cron\CronExpression::isDue()` against the current wall-clock time
 *   (`--now` overrides the reference time for tests and back-fills).
 *   `isDue()` is true for the whole minute in which a cron tick lands,
 *   so calling `schedule:run` twice within the same minute re-fires.
 *   For long-running workers that need once-per-tick de-duplication,
 *   use `--lock=<path>` (advisory flock).
 *
 * Flags:
 *   --force     Fire every discovered schedule, regardless of cron.
 *               Useful for cron-of-crone workflows and staging deploys.
 *   --dry-run   Print what would fire without invoking callables.
 *   --now=...   Override "now" (any strtotime()-parseable string).
 *   --lock=PATH Advisory flock around the run loop — second invocation
 *               aborts with code 0 (silent no-op) instead of doubling up.
 *
 * Exit codes:
 *   0   every (non-skipped) schedule ran to completion
 *   1   one or more callables threw
 *   2   bad cron expression in some schedule (other schedules still ran)
 *
 * Pair with `ScheduleListCommand` (same package) for the introspection side.
 */
#[AsCommand(
    name: 'schedule:run',
    description: 'Run all #[Schedule] tasks whose cron is due now.',
)]
final class ScheduleRunCommand extends Command
{
    public function __construct(private readonly ScheduleDiscovererInterface $discoverer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Fire every discovered schedule, ignoring cron.');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would run without invoking callables.');
        $this->addOption('now', null, InputOption::VALUE_REQUIRED, 'Override the reference time (any strtotime()-parseable string).');
        $this->addOption('lock', null, InputOption::VALUE_REQUIRED, 'Advisory flock path; second concurrent invocation aborts silently.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now = $this->resolveNow((string) $input->getOption('now'));
        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');
        $lockPath = $input->getOption('lock');

        $lockHandle = null;
        if (is_string($lockPath) && $lockPath !== '') {
            $lockHandle = @fopen($lockPath, 'c');
            if ($lockHandle === false) {
                $io->error("Cannot open lock file: $lockPath");
                return 1;
            }
            if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
                // Another worker holds the lock — silent no-op so cron-of-cron
                // setups don't double-fire.
                $io->writeln('<comment>Lock held by another worker — exiting cleanly.</comment>');
                fclose($lockHandle);
                return 0;
            }
        }

        $schedules = $this->discoverer->discover()->all();
        if ($schedules === []) {
            $io->writeln('<comment>(no schedules — define one with #[Schedule] on a static method)</comment>');
            $this->releaseLock($lockHandle);
            return 0;
        }

        $ran = 0;
        $skipped = 0;
        $invalid = 0;
        $errors = [];

        foreach ($schedules as $schedule) {
            $name = $schedule['name'];
            $cron = $schedule['cron'];

            if (!$force) {
                try {
                    $expr = new CronExpression($cron);
                } catch (\Throwable $e) {
                    $invalid++;
                    $errors[] = sprintf('  - %s: invalid cron "%s" (%s)', $name, $cron, $e->getMessage());
                    continue;
                }
                if (!$expr->isDue($now->format('Y-m-d H:i'))) {
                    $skipped++;
                    continue;
                }
            }

            $io->writeln(sprintf(
                '<info>%s</info> %s (%s)',
                $dryRun ? '[dry-run] would run' : 'Running',
                $name,
                $cron,
            ));

            if ($dryRun) {
                $ran++;
                continue;
            }

            try {
                $callable = $schedule['callable'];
                if (is_array($callable) && count($callable) === 2 && is_string($callable[0]) && is_string($callable[1])) {
                    [$class, $method] = $callable;
                    $class::$method();
                } else {
                    $callable();
                }
                $ran++;
            } catch (\Throwable $e) {
                $errors[] = sprintf('  - %s: %s', $name, $e->getMessage());
            }
        }

        if ($invalid > 0) {
            $io->warning(sprintf('%d schedule(s) had an invalid cron expression:', $invalid));
            $io->writeln($errors);
        }

        // Runtime errors (callables that threw) are reported separately
        // from invalid-cron errors. The exit code distinguishes the two:
        // 2 = invalid-only, 1 = at least one callable threw.
        $runtimeErrors = array_slice($errors, $invalid);
        if ($runtimeErrors !== []) {
            $io->error(sprintf('%d schedule(s) raised an exception during execution:', count($runtimeErrors)));
            $io->writeln($runtimeErrors);
        }

        $io->writeln(sprintf(
            'Done. ran=%d, skipped=%d, invalid=%d, errors=%d, dry-run=%s, force=%s',
            $ran,
            $skipped,
            $invalid,
            count($errors) - $invalid,
            $dryRun ? 'yes' : 'no',
            $force ? 'yes' : 'no',
        ));

        $this->releaseLock($lockHandle);

        if ($invalid > 0 && $ran === 0 && count($errors) - $invalid === 0) {
            // Only invalid expressions and nothing else ran.
            return 2;
        }
        if (count($errors) - $invalid > 0) {
            return 1;
        }
        return 0;
    }

    private function resolveNow(string $override): \DateTimeImmutable
    {
        if ($override === '') {
            return new \DateTimeImmutable('now');
        }
        try {
            return new \DateTimeImmutable($override);
        } catch (\Throwable) {
            return new \DateTimeImmutable('now');
        }
    }

    /**
     * @param resource|null $handle
     */
    private function releaseLock($handle): void
    {
        if ($handle === null) {
            return;
        }
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
