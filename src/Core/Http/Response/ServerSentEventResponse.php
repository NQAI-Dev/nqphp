<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * HTTP response for Server-Sent Events (SSE) streaming.
 */
class ServerSentEventResponse extends StreamedResponse
{
    /**
     * @param iterable<mixed>|callable(\Closure(string, ?string=, ?string=, ?int=): void): void $eventProducer
     * @param int $status
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        iterable|callable $eventProducer,
        int $status = 200,
        array $headers = []
    ) {
        $defaultHeaders = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        $callback = function () use ($eventProducer): void {
            $sender = function (string $data, ?string $event = null, ?string $id = null, ?int $retry = null): void {
                if ($id !== null) {
                    echo "id: {$id}\n";
                }
                if ($event !== null) {
                    echo "event: {$event}\n";
                }
                if ($retry !== null) {
                    echo "retry: {$retry}\n";
                }

                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    echo "data: {$line}\n";
                }
                echo "\n";

                flush();
            };

            if (is_callable($eventProducer)) {
                $eventProducer($sender);
            } elseif (is_iterable($eventProducer)) {
                foreach ($eventProducer as $item) {
                    if (is_array($item)) {
                        $sender(
                            (string) ($item['data'] ?? ''),
                            isset($item['event']) ? (string) $item['event'] : null,
                            isset($item['id']) ? (string) $item['id'] : null,
                            isset($item['retry']) ? (int) $item['retry'] : null
                        );
                    } else {
                        $sender((string) $item);
                    }
                }
            }
        };

        parent::__construct($callback, $status, $headers);
    }
}
