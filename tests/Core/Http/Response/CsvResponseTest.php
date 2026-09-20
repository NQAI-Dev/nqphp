<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\CsvResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CsvResponseTest extends TestCase
{
    public function testDefaultCsvFormatting(): void
    {
        $rows = [
            ['id', 'name', 'email'],
            [1, 'Alice', 'alice@example.com'],
            [2, 'Bob, Jr.', 'bob@example.com'],
        ];

        $response = new CsvResponse($rows);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertFalse($response->headers->has('Content-Disposition'));

        $expected = "id,name,email\n1,Alice,alice@example.com\n2,\"Bob, Jr.\",bob@example.com\n";
        $this->assertSame($expected, $response->getContent());
    }

    public function testWithFilenameGeneratesContentDisposition(): void
    {
        $rows = [
            ['sku', 'price'],
            ['A1', '10.50'],
        ];

        $response = new CsvResponse($rows, filename: 'export.csv');

        $this->assertSame('attachment; filename="export.csv"', $response->headers->get('Content-Disposition'));
    }

    public function testCustomDelimiterAndStatus(): void
    {
        $rows = [
            ['a', 'b'],
            ['1', '2'],
        ];

        $response = new CsvResponse($rows, delimiter: ';', status: 201);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame("a;b\n1;2\n", $response->getContent());
    }
}
