<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http;

use Nqphp\Core\Http\StreamedResponse;
use PHPUnit\Framework\TestCase;

class StreamedResponseTest extends TestCase
{
    public function testFormatEventSimple(): void
    {
        $payload = StreamedResponse::formatEvent('hello world');
        $this->assertSame("data: hello world\n\n", $payload);
    }

    public function testFormatEventWithAllFields(): void
    {
        $payload = StreamedResponse::formatEvent(
            data: ['msg' => 'ok', 'count' => 10],
            event: 'update',
            id: '123',
            retry: 5000
        );

        $expected = "id: 123\n" .
            "event: update\n" .
            "retry: 5000\n" .
            "data: {\"msg\":\"ok\",\"count\":10}\n\n";

        $this->assertSame($expected, $payload);
    }

    public function testFormatEventMultiline(): void
    {
        $payload = StreamedResponse::formatEvent("line 1\nline 2\nline 3");
        $expected = "data: line 1\ndata: line 2\ndata: line 3\n\n";
        $this->assertSame($expected, $payload);
    }

    public function testSseFactorySetsHeaders(): void
    {
        $response = StreamedResponse::sse(function () {});

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-transform', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('keep-alive', $response->headers->get('Connection'));
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));
    }

    public function testSseFactoryAllowsCustomHeaders(): void
    {
        $response = StreamedResponse::sse(function () {}, 200, ['X-Custom' => 'test']);
        $this->assertSame('test', $response->headers->get('X-Custom'));
    }

    public function testStreamCallbackExecution(): void
    {
        $response = new StreamedResponse(function () {
            echo 'chunk1';
            echo 'chunk2';
        });

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame('chunk1chunk2', $output);
    }
}
