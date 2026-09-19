<?php

declare(strict_types=1);

namespace Nqphp\Core\Scheduler;

use Cron\CronExpression;
use Nqphp\Core\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `bin/console schedule:list` — render every #[Schedule]-discovered
 * task as a Symfony Console table.
 *
 * Columns:
 *   - NAME        — explicit `#[Schedule(name: ...)]` or "{Class}::{method}"
 *   - CRON        — the cron expression (5-field or `@hourly`/`@daily`/...)
 *   - NEXT RUN    — next firing time, in the local timezone (or "(invalid)"
 *                   if the expression does not parse)
 *   - DESCRIPTION — one-line description from #[Schedule(description: ...)]
 *
 * The `--raw` flag emits TSV (tab-separated values) for scripting — no
 * ANSI codes, no table borders. Handy for `bin/console schedule:list
 * --raw | awk -F'\t' '$2 == "@hourly"'`.
 *
 * The cron evaluator is `dragonmantank/cron-expression` (the same
 * library that powers `Symfony\Component\Scheduler\Trigger\CronExpressionTrigger`).
 * Schedule entries whose cron string fails to parse still appear in the
 * output, but with NEXT RUN = "(invalid)" — we never silently drop a
 * schedule because it didn't parse, since that hides typos.
 *
 * Phase 2 #4 follow-up: pair with `ScheduleRunCommand` (same package)
 * to actually fire the due-now callables.
 */
#[AsCommand(
    name: 'schedule:list',
    description: 'List all #[Schedule]-annotated tasks with their next run time.',
)]
final class ScheduleListCommand extends Command
{
    public function __construct(private readonly ScheduleDiscoverer $discoverer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('raw', null, InputOption::VALUE_NONE, 'Emit TSV (no table borders, no ANSI) for scripts.');
        $this->addOption('timezone', 'tz', InputOption::VALUE_REQUIRED, 'Timezone for NEXT RUN computation (default: UTC).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schedules = $this->discoverer->discover()->all();
        if ($schedules === []) {
            $output->writeln('<comment>(no schedules — define one with #[Schedule] on a static method)</comment>');
            return self::SUCCESS;
        }

        $tz = $this->resolveTimezone((string) $input->getOption('timezone'));
        $now = new \DateTimeImmutable('now', $tz);

        $rows = [];
        foreach ($schedules as $schedule) {
            $rows[] = [
                $schedule['name'],
                $schedule['cron'],
                $this->formatNextRun($schedule['cron'], $now, $tz),
                $schedule['description'],
            ];
        }

        if ((bool) $input->getOption('raw')) {
            // TSV: no ANSI, stable ordering. Header on first line for grep/awk friendliness.
            $output->writeln("NAME\tCRON\tNEXT RUN\tDESCRIPTION");
            foreach ($rows as $r) {
                $output->writeln(implode("\t", [
                    $this->stripControl($r[0]),
                    $this->stripControl($r[1]),
                    $this->stripControl($r[2]),
                    $this->stripControl($r[3]),
                ]));
            }
            return self::SUCCESS;
        }

        $output->writeln('<info>Discovered schedules</info>');
        $this->renderTable($output, $rows);
        return self::SUCCESS;
    }

    /**
     * @param list<array{0:string,1:string,2:string,3:string}> $rows
     */
    private function renderTable(OutputInterface $output, array $rows): void
    {
        // Plain rows + separators + headers rendered manually so we don't
        // need a Table helper section + can interleave "(invalid)" rows
        // visually with no surprises. Symfony's Table helper would do,
        // but a hand-rolled format keeps column alignment consistent
        // across terminals and avoids Table helper layout quirks for
        // long descriptions.
        $widths = [0, 0, 0, 0];
        $headers = ['NAME', 'CRON', 'NEXT RUN', 'DESCRIPTION'];
        foreach ($headers as $i => $h) {
            $widths[$i] = max($widths[$i], strlen($h));
        }
        foreach ($rows as $r) {
            foreach ($r as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen($this->stripAnsi($cell)));
            }
        }

        $output->writeln($this->renderRow($headers, $widths));
        $output->writeln($this->renderSeparator($widths));
        foreach ($rows as $i => $r) {
            if ($i > 0 && ($r[2] === '(invalid)' || $rows[$i - 1][2] === '(invalid)')) {
                // Visual break around rows that failed to parse — they're
                // the actionable ones (typo to fix) so we want them to
                // stand out a touch. Subtle separator, not a full gap.
            }
            $output->writeln($this->renderRow($r, $widths));
        }
    }

    /**
     * @param list<int> $widths
     */
    private function renderRow(array $cells, array $widths): string
    {
        $parts = [];
        foreach ($cells as $i => $cell) {
            // Cols 0..2 fixed width; DESCRIPTION takes the rest (no padding)
            // so long descriptions wrap naturally without breaking alignment.
            if ($i < 3) {
                $parts[] = str_pad($this->stripAnsi($cell), $widths[$i]);
            } else {
                $parts[] = $cell;
            }
        }
        return '  ' . implode('  ', $parts);
    }

    /**
     * @param list<int> $widths
     */
    private function renderSeparator(array $widths): string
    {
        $parts = [];
        foreach ($widths as $i => $w) {
            if ($i < 3) {
                $parts[] = str_repeat('-', $w);
            }
            // No separator after the description column — it's free-form.
        }
        return '  ' . implode('  ', $parts);
    }

    private function formatNextRun(string $cron, \DateTimeImmutable $now, \DateTimeZone $tz): string
    {
        try {
            $expr = new CronExpression($cron);
        } catch (\Throwable) {
            return '(invalid)';
        }
        // dragonmantank/cron-expression expects a timezone *string* in
        // getNextRunDate() — passing a DateTimeZone object throws a TypeError.
        // The returned DateTime is rendered in the same timezone so the
        // operator sees a wall-clock value that matches the cron intent.
        try {
            $next = $expr->getNextRunDate($now->format('Y-m-d H:i:s'), 0, false, $tz->getName());
        } catch (\Throwable) {
            return '(invalid)';
        }
        return $next->setTimezone($tz)->format('Y-m-d H:i T');
    }

    private function resolveTimezone(string $tz): \DateTimeZone
    {
        if ($tz === '') {
            return new \DateTimeZone('UTC');
        }
        try {
            return new \DateTimeZone($tz);
        } catch (\Throwable) {
            return new \DateTimeZone('UTC');
        }
    }

    private function stripAnsi(string $s): string
    {
        return (string) preg_replace('/\x1b\[[0-9;]*m/', '', $s);
    }

    private function stripControl(string $s): string
    {
        // Strip ANSI escapes + replace tabs/newlines so the TSV stays
        // single-line per row.
        $s = $this->stripAnsi($s);
        return (string) preg_replace('/[\t\r\n]+/', ' ', $s);
    }
}
