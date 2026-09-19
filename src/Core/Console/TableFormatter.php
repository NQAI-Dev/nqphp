<?php

declare(strict_types=1);

namespace Nqphp\Core\Console;

/**
 * Formats tabular console data without coupling callers to an OutputInterface.
 *
 * Cell widths account for ANSI colour sequences, multibyte characters and
 * multiline values. Every logical row may therefore produce several lines.
 */
final class TableFormatter
{
    /**
     * @param list<string>       $headers
     * @param list<list<string>> $rows
     *
     * @return list<string>
     */
    public function format(array $headers, array $rows): array
    {
        if ($headers === []) {
            throw new \InvalidArgumentException('Table must have at least one column.');
        }

        $columnCount = count($headers);
        foreach ($rows as $index => $row) {
            if (count($row) !== $columnCount) {
                throw new \InvalidArgumentException(sprintf(
                    'Row %d has %d cells; expected %d.',
                    $index,
                    count($row),
                    $columnCount,
                ));
            }
        }

        $widths = array_fill(0, $columnCount, 0);
        foreach ([$headers, ...$rows] as $row) {
            foreach ($row as $column => $cell) {
                foreach ($this->lines($cell) as $line) {
                    $widths[$column] = max($widths[$column], $this->visibleWidth($line));
                }
            }
        }

        $result = $this->formatRow($headers, $widths);
        $result[] = '  '.implode('  ', array_map(
            static fn (int $width): string => str_repeat('-', $width),
            $widths,
        ));

        foreach ($rows as $row) {
            array_push($result, ...$this->formatRow($row, $widths));
        }

        return $result;
    }

    /**
     * @param list<string> $cells
     * @param list<int>    $widths
     *
     * @return list<string>
     */
    private function formatRow(array $cells, array $widths): array
    {
        $cellLines = array_map($this->lines(...), $cells);
        $height = max(array_map(count(...), $cellLines));
        $result = [];

        for ($line = 0; $line < $height; ++$line) {
            $parts = [];
            foreach ($cellLines as $column => $lines) {
                $value = $lines[$line] ?? '';
                $parts[] = $value.str_repeat(' ', $widths[$column] - $this->visibleWidth($value));
            }
            $result[] = '  '.rtrim(implode('  ', $parts));
        }

        return $result;
    }

    /** @return non-empty-list<string> */
    private function lines(string $value): array
    {
        return preg_split('/\R/u', $value) ?: [''];
    }

    private function visibleWidth(string $value): int
    {
        $plain = preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', $value) ?? $value;

        return mb_strwidth($plain, 'UTF-8');
    }
}
