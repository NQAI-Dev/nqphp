<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MetricsHttpClient implements HttpClientInterface
{
    /** @var array<string, int> */
    private array $requestCount = [];

    /** @var array<string, int> */
    private array $errorCount = [];

    /** @var array<string, list<float>> */
    private array $latencies = [];

    private float $totalLatency = 0.0;

    private int $totalRequests = 0;

    public function __construct(
        private readonly HttpClientInterface $client,
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $host = (string) (parse_url($url, PHP_URL_HOST) ?: 'default');
        $start = microtime(true);

        try {
            $response = $this->client->request($method, $url, $options);
            $latency = (microtime(true) - $start) * 1000;

            $this->requestCount[$host] = ($this->requestCount[$host] ?? 0) + 1;
            $this->latencies[$host][] = $latency;
            $this->totalLatency += $latency;
            $this->totalRequests++;

            if ($response->getStatusCode() >= 400) {
                $this->errorCount[$host] = ($this->errorCount[$host] ?? 0) + 1;
            }

            return $response;
        } catch (Throwable $e) {
            $latency = (microtime(true) - $start) * 1000;
            $this->requestCount[$host] = ($this->requestCount[$host] ?? 0) + 1;
            $this->errorCount[$host] = ($this->errorCount[$host] ?? 0) + 1;
            $this->latencies[$host][] = $latency;
            $this->totalLatency += $latency;
            $this->totalRequests++;

            throw $e;
        }
    }

    public function get(string $url, array $options = []): Response
    {
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $options = []): Response
    {
        return $this->request('POST', $url, $options);
    }

    public function put(string $url, array $options = []): Response
    {
        return $this->request('PUT', $url, $options);
    }

    public function delete(string $url, array $options = []): Response
    {
        return $this->request('DELETE', $url, $options);
    }

    public function patch(string $url, array $options = []): Response
    {
        return $this->request('PATCH', $url, $options);
    }

    /**
     * @return array<string, array{requests: int, errors: int, avg_latency_ms: float, p95_latency_ms: float}>
     */
    public function getMetrics(): array
    {
        $metrics = [];

        foreach ($this->requestCount as $host => $count) {
            $hostLatencies = $this->latencies[$host] ?? [];
            sort($hostLatencies);

            $metrics[$host] = [
                'requests' => $count,
                'errors' => $this->errorCount[$host] ?? 0,
                'avg_latency_ms' => $hostLatencies === [] ? 0.0 : array_sum($hostLatencies) / count($hostLatencies),
                'p95_latency_ms' => $hostLatencies === [] ? 0.0 : $hostLatencies[min(count($hostLatencies) - 1, (int) floor(count($hostLatencies) * 0.95))],
            ];
        }

        return $metrics;
    }

    public function getGlobalMetrics(): array
    {
        return [
            'total_requests' => $this->totalRequests,
            'total_errors' => array_sum($this->errorCount),
            'avg_latency_ms' => $this->totalRequests === 0 ? 0.0 : $this->totalLatency / $this->totalRequests,
        ];
    }

    public function resetMetrics(): void
    {
        $this->requestCount = [];
        $this->errorCount = [];
        $this->latencies = [];
        $this->totalLatency = 0.0;
        $this->totalRequests = 0;
    }
}
