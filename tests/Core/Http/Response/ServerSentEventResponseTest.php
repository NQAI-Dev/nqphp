<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\ServerSentEventResponse;
use PHPUnit\Framework\TestCase;

class ServerSentEventResponseTest extends TestCase
{
    public function testSetsDefaultSseHeaders(): void
    {
        $response = new ServerSentEventResponse([]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertSame('keep-alive', $response->headers->get('Connection'));
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));
    }

    public function testStreamsFromIterableStrings(): void
    {
        $response = new ServerSentEventResponse(['hello', 'world']);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $expected = "data: hello\n\ndata: world\n\n";
        $this->assertSame($expected, $output);
    }

    public function testStreamsFromIterableStructuredEvents(): void
    {
        $events = [
            [
                'id' => '1',
                'event' => 'ping',
                'data' => "line1\nline2",
                'retry' => 5000,
            ],
            [
                'data' => 'simple',
            ],
        ];

        $response = new ServerSentEventResponse($events);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $expected = "id: 1\nevent: ping\nretry: 5000\ndata: line1\ndata: line2\n\ndata: simple\n\n";
        $this->assertSame($expected, $output);
    }

    public function testStreamsFromCallable(): void
    {
        $producer = function ($send): void {
            $send('message payload', 'custom-event', 'evt-42');
        };

        $response = new ServerSentEventResponse($producer);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $expected = "id: evt-42\nevent: custom-event\ndata: message payload\n\n";
        $this->assertSame($expected, $output);
    }
}
