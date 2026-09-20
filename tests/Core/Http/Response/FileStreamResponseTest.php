<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use InvalidArgumentException;
use Nqphp\Core\Http\Response\FileStreamResponse;
use PHPUnit\Framework\TestCase;

class FileStreamResponseTest extends TestCase
{
    public function testRequiresValidStreamResource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Аргумент $stream должен быть корректным ресурсом потока.');

        new FileStreamResponse('not-a-resource');
    }

    public function testStreamsContentInChunks(): void
    {
        $stream = fopen('php://temp', 'r+');
        $content = 'Test stream content chunk 12345';
        fwrite($stream, $content);
        rewind($stream);

        $response = new FileStreamResponse($stream, 200, ['Content-Type' => 'text/plain'], chunkSize: 8);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame($content, $output);
    }
}
