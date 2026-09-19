<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HttpClientException;
use Nqphp\Core\Http\Client\MockHttpClient;
use Nqphp\Core\Http\Client\RetryHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class RetryHttpClientTest extends TestCase
{
    public function testReturnsImmediateSuccess(): void
    {
        $mock = new MockHttpClient([
            new Response('OK', 200),
        ]);
        $retryClient = new RetryHttpClient($mock, maxRetries: 2, baseDelayMs: 1);

        $response = $retryClient->get('https://example.com/api');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', (string) $response->getContent());
        $this->assertSame(1, $mock->count());
    }

    public function testRetriesOnTransientHttpStatus(): void
    {
        $mock = new MockHttpClient([
            new Response('Gateway Timeout', 504),
            new Response('Service Unavailable', 503),
            new Response('Success', 200),
        ]);
        $retryClient = new RetryHttpClient($mock, maxRetries: 3, baseDelayMs: 1);

        $response = $retryClient->post('https://example.com/pay', ['json' => ['amount' => 10]]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Success', (string) $response->getContent());
        $this->assertSame(3, $mock->count());
    }

    public function testRetriesOnNetworkException(): void
    {
        $mock = new MockHttpClient([
            function () {
                throw new HttpClientException('Connection reset');
            },
            new Response('Recovered', 200),
        ]);
        $retryClient = new RetryHttpClient($mock, maxRetries: 2, baseDelayMs: 1);

        $response = $retryClient->get('https://example.com/unstable');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Recovered', (string) $response->getContent());
        $this->assertSame(2, $mock->count());
    }

    public function testFailsWhenMaxRetriesExceeded(): void
    {
        $mock = new MockHttpClient([
            new Response('Bad Gateway', 502),
            new Response('Bad Gateway', 502),
        ]);
        $retryClient = new RetryHttpClient($mock, maxRetries: 1, baseDelayMs: 1);

        $response = $retryClient->get('https://example.com/down');

        $this->assertSame(502, $response->getStatusCode());
        $this->assertSame(2, $mock->count());
    }
}
