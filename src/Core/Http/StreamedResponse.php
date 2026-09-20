<?php

declare(strict_types=1);

namespace Nqphp\Core\Http;

use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;

/**
 * Native macroframework StreamedResponse for streaming large responses,
 * chunked transfers, and Server-Sent Events (SSE).
 */
class StreamedResponse extends SymfonyStreamedResponse
{
    /**
     * Factory for creating Server-Sent Events (SSE) streaming responses.
     * Automatically sets headers for keep-alive, text/event-stream, and disabling proxy buffering.
     *
     * @param callable(): void $callback
     */
    public static function sse(callable $callback, int $status = 200, array $headers = []): static
    {
        $defaultHeaders = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable buffering in Nginx/Angie
        ];

        return new static(
            $callback,
            $status,
            array_merge($defaultHeaders, $headers)
        );
    }

    /**
     * Format a standard Server-Sent Event message payload.
     *
     * @param string|array<string, mixed> $data
     */
    public static function formatEvent(string|array $data, ?string $event = null, ?string $id = null, ?int $retry = null): string
    {
        $payload = '';

        if ($id !== null) {
            $payload .= "id: {$id}\n";
        }

        if ($event !== null) {
            $payload .= "event: {$event}\n";
        }

        if ($retry !== null) {
            $payload .= "retry: {$retry}\n";
        }

        $text = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : $data;
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $payload .= "data: {$line}\n";
        }

        return $payload . "\n";
    }
}
