<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\TimeoutHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class TimeoutHttpClientTest extends TestCase
{
    public function testRequestAppliesDefaultTimeout(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://example.com/api', $this->callback(function (array $options): bool {
                return isset($options['timeout']) && $options['timeout'] === 5.0;
            }))
            ->willReturn(new Response('ok', 200));

        $client = new TimeoutHttpClient($mock, defaultTimeout: 5.0);
        $client->request('GET', 'https://example.com/api');
    }

    public function testRequestPreservesExplicitTimeoutUnderMax(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with('POST', 'https://example.com/upload', $this->callback(function (array $options): bool {
                return isset($options['timeout']) && $options['timeout'] === 12.0;
            }))
            ->willReturn(new Response('ok', 200));

        $client = new TimeoutHttpClient($mock, defaultTimeout: 5.0, maxTimeout: 30.0);
        $client->request('POST', 'https://example.com/upload', ['timeout' => 12.0]);
    }

    public function testRequestClampsTimeoutToMax(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://example.com/slow', $this->callback(function (array $options): bool {
                return isset($options['timeout']) && $options['timeout'] === 20.0;
            }))
            ->willReturn(new Response('ok', 200));

        $client = new TimeoutHttpClient($mock, defaultTimeout: 5.0, maxTimeout: 20.0);
        $client->request('GET', 'https://example.com/slow', ['timeout' => 60.0]);
    }

    public function testConvenienceHttpMethodsDelegateWithTimeout(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->exactly(5))
            ->method('request')
            ->willReturn(new Response('ok', 200));

        $client = new TimeoutHttpClient($mock, defaultTimeout: 8.0);
        $client->get('https://example.com');
        $client->post('https://example.com');
        $client->put('https://example.com');
        $client->patch('https://example.com');
        $client->delete('https://example.com');
    }
}
