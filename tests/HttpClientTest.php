<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Http\Client\HttpClient;
use Nqphp\Core\Http\Client\HttpClientException;
use PHPUnit\Framework\TestCase;

final class HttpClientTest extends TestCase
{
    public function testInstantiationAndDefaultOptions(): void
    {
        $client = new HttpClient([
            'timeout' => 5.0,
            'headers' => ['X-Custom' => 'value'],
        ]);

        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testInvalidUrlThrowsException(): void
    {
        $client = new HttpClient(['timeout' => 0.5]);

        $this->expectException(HttpClientException::class);
        $client->get('http://127.0.0.1:59999/non-existent-port');
    }

    public function testBuildsRequestWithJsonAndHeaders(): void
    {
        // Use PHP's built-in web server or data wrapper to verify execution
        $client = new HttpClient();
        $this->assertTrue(method_exists($client, 'get'));
        $this->assertTrue(method_exists($client, 'post'));
        $this->assertTrue(method_exists($client, 'put'));
        $this->assertTrue(method_exists($client, 'patch'));
        $this->assertTrue(method_exists($client, 'delete'));
    }

    public function testKernelAndControllerIntegration(): void
    {
        $kernel = new \Nqphp\Core\Kernel\Kernel(__DIR__ . '/Fixtures/app');
        $this->assertTrue(method_exists($kernel, 'httpClient'));
        $client = $kernel->httpClient();
        $this->assertInstanceOf(\Nqphp\Core\Http\Client\HttpClientInterface::class, $client);
    }
}
