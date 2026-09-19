<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Console\TableFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TableFormatterTest extends TestCase
{
    public function testFormatsHeadersAndRows(): void
    {
        $lines = (new TableFormatter())->format(
            ['NAME', 'ROLE'],
            [['Ada', 'admin'], ['Maximilian', 'user']],
        );

        self::assertSame([
            '  NAME        ROLE',
            '  ----------  -----',
            '  Ada         admin',
            '  Maximilian  user',
        ], $lines);
    }

    public function testAlignsUnicodeAndAnsiCellsByVisibleWidth(): void
    {
        $lines = (new TableFormatter())->format(
            ['STATUS', 'NAME'],
            [["\033[32mOK\033[0m", 'Ёж'], ['ERROR', 'Mia']],
        );

        self::assertSame("  \033[32mOK\033[0m      Ёж", $lines[2]);
        self::assertSame('  ERROR   Mia', $lines[3]);
    }

    public function testExpandsMultilineCellsIntoPhysicalRows(): void
    {
        $lines = (new TableFormatter())->format(
            ['NAME', 'DETAIL'],
            [['job', "first\nsecond"]],
        );

        self::assertSame([
            '  NAME  DETAIL',
            '  ----  ------',
            '  job   first',
            '        second',
        ], $lines);
    }

    #[DataProvider('invalidTables')]
    public function testRejectsInvalidTableShape(array $headers, array $rows, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new TableFormatter())->format($headers, $rows);
    }

    public static function invalidTables(): iterable
    {
        yield 'no columns' => [[], [], 'Table must have at least one column.'];
        yield 'short row' => [['A', 'B'], [['only one']], 'Row 0 has 1 cells; expected 2.'];
    }
}
