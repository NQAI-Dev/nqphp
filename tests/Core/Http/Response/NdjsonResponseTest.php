<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use InvalidArgumentException;
use Nqphp\Core\Http\Response\NdjsonResponse;
use PHPUnit\Framework\TestCase;

class NdjsonResponseTest extends TestCase
{
    public function testRendersEmptyDataset(): void
    {
        $response = new NdjsonResponse([]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/x-ndjson; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('', $response->getContent());
    }

    public function testRendersRowsWithNewlineSeparation(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $response = new NdjsonResponse($rows);
        $expected = "{\"id\":1,\"name\":\"Alice\"}\n{\"id\":2,\"name\":\"Bob\"}\n";

        $this->assertSame($expected, $response->getContent());
    }

    public function testAcceptsTraversableGenerator(): void
    {
        $gen = function () {
            yield ['step' => 1];
            yield ['step' => 2];
        };

        $response = new NdjsonResponse($gen(), 201);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame("{\"step\":1}\n{\"step\":2}\n", $response->getContent());
    }

    public function testThrowsOnInvalidJsonEncoding(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to encode NDJSON item');

        new NdjsonResponse([['invalid' => "\xB1\x31"]]);
    }
}
