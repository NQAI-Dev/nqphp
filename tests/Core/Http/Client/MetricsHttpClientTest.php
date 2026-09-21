<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\MetricsHttpClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class MetricsHttpClientTest extends TestCase
{
    public function testCountsSuccessfulRequestsPerHost(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->method('request')->willReturn(new Response('ok', 200));

        $client = new MetricsHttpClient($mock);

        $client->get('https://api-one.example.com/data');
        $client->get('https://api-one.example.com/data');
        $client->get('https://api-two.example.com/data');

        $metrics = $client->getMetrics();

        $this->assertSame(2, $metrics['api-one.example.com']['requests']);
        $this->assertSame(1, $metrics['api-two.example.com']['requests']);
        $this->assertSame(0, $metrics['api-one.example.com']['errors']);
    }

    public function testCountsHttpErrorsAndExceptions(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->method('request')->willReturnOnConsecutiveCalls(
            new Response('err', 500),
            $this->throwException(new RuntimeException('boom'))
        );

        $client = new MetricsHttpClient($mock);

        try {
            $client->get('https://api.example.com/fail');
        } catch (RuntimeException) {
        }

        try {
            $client->get('https://api.example.com/fail');
        } catch (RuntimeException) {
        }

        $metrics = $client->getMetrics();

        $this->assertSame(2, $metrics['api.example.com']['requests']);
        $this->assertSame(2, $metrics['api.example.com']['errors']);
    }

    public function testLatencyAggregation(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->method('request')->willReturnCallback(static function (): Response {
            usleep(1000); // ~1ms
            return new Response('ok', 200);
        });

        $client = new MetricsHttpClient($mock);
        $client->get('https://api.example.com/data');
        $client->get('https://api.example.com/data');

        $metrics = $client->getMetrics();
        $global = $client->getGlobalMetrics();

        $this->assertGreaterThan(0.0, $metrics['api.example.com']['avg_latency_ms']);
        $this->assertGreaterThan(0.0, $metrics['api.example.com']['p95_latency_ms']);
        $this->assertSame(2, $global['total_requests']);
        $this->assertSame(0, $global['total_errors']);
    }

    public function testResetMetricsClearsEverything(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->method('request')->willReturn(new Response('ok', 200));

        $client = new MetricsHttpClient($mock);
        $client->get('https://api.example.com/data');

        $client->resetMetrics();

        $this->assertSame([], $client->getMetrics());
        $this->assertSame(0, $client->getGlobalMetrics()['total_requests']);
    }
}
