<?php

declare(strict_types=1);

namespace Nqphp\Core\Monitoring;

use Nqphp\Core\Http\Client\HttpClientInterface;
use Throwable;

/**
 * Health check that pings an external HTTP endpoint and reports status based on latency and HTTP code.
 */
class PingHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $url,
        private readonly string $name = 'ping',
        private readonly int $timeoutSeconds = 5,
        private readonly float $maxLatencyMs = 2000.0,
        private readonly int $expectedStatusCode = 200
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function check(): array
    {
        $start = microtime(true);

        try {
            $response = $this->client->get($this->url, [
                'timeout' => $this->timeoutSeconds,
            ]);

            $durationMs = round((microtime(true) - $start) * 1000, 2);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== $this->expectedStatusCode) {
                return [
                    'status' => 'degraded',
                    'message' => sprintf('Unexpected HTTP status %d (expected %d)', $statusCode, $this->expectedStatusCode),
                    'meta' => [
                        'url' => $this->url,
                        'status_code' => $statusCode,
                        'duration_ms' => $durationMs,
                    ],
                ];
            }

            if ($durationMs > $this->maxLatencyMs) {
                return [
                    'status' => 'degraded',
                    'message' => sprintf('High latency: %.2fms (threshold: %.2fms)', $durationMs, $this->maxLatencyMs),
                    'meta' => [
                        'url' => $this->url,
                        'status_code' => $statusCode,
                        'duration_ms' => $durationMs,
                    ],
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Endpoint reachable',
                'meta' => [
                    'url' => $this->url,
                    'status_code' => $statusCode,
                    'duration_ms' => $durationMs,
                ],
            ];
        } catch (Throwable $e) {
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'down',
                'message' => sprintf('Ping failed: %s', $e->getMessage()),
                'meta' => [
                    'url' => $this->url,
                    'duration_ms' => $durationMs,
                ],
            ];
        }
    }
}
