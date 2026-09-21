<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\AcceptHeaderHttpClient;
use Nqphp\Core\Http\Client\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AcceptHeaderHttpClientTest extends TestCase
{
    public function testSetsDefaultAcceptHeaderWhenNotProvided(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.example.com/users',
                $this->callback(function (array $options) {
                    return isset($options['headers']['Accept']) && $options['headers']['Accept'] === 'application/json';
                })
            )
            ->willReturn(new Response('[]', 200));

        $client = new AcceptHeaderHttpClient($mock);
        $response = $client->request('GET', 'https://api.example.com/users');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPreservesExistingAcceptHeader(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.example.com/upload',
                $this->callback(function (array $options) {
                    return isset($options['headers']['accept']) && $options['headers']['accept'] === 'text/html';
                })
            )
            ->willReturn(new Response('OK', 200));

        $client = new AcceptHeaderHttpClient($mock, 'application/json');
        $response = $client->request('POST', 'https://api.example.com/upload', [
            'headers' => ['accept' => 'text/html'],
        ]);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSupportsCustomDefaultAcceptValue(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.example.com/data.xml',
                $this->callback(function (array $options) {
                    return isset($options['headers']['Accept']) && $options['headers']['Accept'] === 'application/xml';
                })
            )
            ->willReturn(new Response('<data/>', 200));

        $client = new AcceptHeaderHttpClient($mock, 'application/xml');
        $response = $client->request('GET', 'https://api.example.com/data.xml');

        $this->assertSame(200, $response->getStatusCode());
    }
}
