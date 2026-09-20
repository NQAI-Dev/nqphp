<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Monitoring;

use Nqphp\Core\Http\Client\HttpClientException;
use Nqphp\Core\Http\Client\MockHttpClient;
use Nqphp\Core\Monitoring\HealthCheckInterface;
use Nqphp\Core\Monitoring\PingHealthCheck;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PingHealthCheckTest extends TestCase
{
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
    }

    public function testImplementsHealthCheckInterface(): void
    {
        $check = new PingHealthCheck($this->mockClient, 'https://example.com/health');
        $this->assertInstanceOf(HealthCheckInterface::class, $check);
        $this->assertSame('ping', $check->getName());
    }

    public function testCheckReturnsOkOnExpectedResponse(): void
    {
        $this->mockClient->queueResponse(new Response('OK', 200));

        $check = new PingHealthCheck($this->mockClient, 'https://example.com/health', 'api-ping');
        $this->assertSame('api-ping', $check->getName());

        $result = $check->check();
        $this->assertSame('ok', $result['status']);
        $this->assertSame('Endpoint reachable', $result['message']);
        $this->assertSame('https://example.com/health', $result['meta']['url']);
        $this->assertSame(200, $result['meta']['status_code']);
        $this->assertArrayHasKey('duration_ms', $result['meta']);
    }

    public function testCheckReturnsDegradedOnUnexpectedStatusCode(): void
    {
        $this->mockClient->queueResponse(new Response('Server Error', 500));

        $check = new PingHealthCheck($this->mockClient, 'https://example.com/health', 'api-ping', 5, 2000.0, 200);
        $result = $check->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertStringContainsString('Unexpected HTTP status 500 (expected 200)', $result['message']);
        $this->assertSame(500, $result['meta']['status_code']);
    }

    public function testCheckReturnsDegradedOnHighLatency(): void
    {
        $this->mockClient->queueResponse(new Response('OK', 200));

        // Setting threshold to -1 ms guarantees high latency detection
        $check = new PingHealthCheck($this->mockClient, 'https://example.com/health', 'api-ping', 5, -1.0, 200);
        $result = $check->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertStringContainsString('High latency:', $result['message']);
    }

    public function testCheckReturnsDownOnException(): void
    {
        $this->mockClient->queueResponse(function () {
            throw new HttpClientException('Connection timed out');
        });

        $check = new PingHealthCheck($this->mockClient, 'https://example.com/health', 'api-ping');
        $result = $check->check();

        $this->assertSame('down', $result['status']);
        $this->assertStringContainsString('Ping failed: Connection timed out', $result['message']);
        $this->assertSame('https://example.com/health', $result['meta']['url']);
    }
}
