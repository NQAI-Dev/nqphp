<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HeaderClient;
use Nqphp\Core\Http\Client\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HeaderClientTest extends TestCase
{
    public function testInjectsDefaultHeaders(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.example.com',
                $this->callback(function (array $options) {
                    return isset($options['headers']['X-Custom-Header'])
                        && $options['headers']['X-Custom-Header'] === 'custom-val'
                        && isset($options['headers']['X-Api-Key'])
                        && $options['headers']['X-Api-Key'] === 'secret';
                })
            )
            ->willReturn(new Response('OK', 200));

        $client = new HeaderClient($mock, [
            'X-Custom-Header' => 'custom-val',
            'X-Api-Key' => 'secret',
        ]);

        $response = $client->get('https://api.example.com');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDoesNotOverwriteExistingHeadersCaseInsensitively(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.example.com',
                $this->callback(function (array $options) {
                    return isset($options['headers']['x-custom-header'])
                        && $options['headers']['x-custom-header'] === 'caller-val'
                        && !isset($options['headers']['X-Custom-Header'])
                        && isset($options['headers']['X-Other'])
                        && $options['headers']['X-Other'] === 'other-val';
                })
            )
            ->willReturn(new Response('Created', 201));

        $client = new HeaderClient($mock, [
            'X-Custom-Header' => 'default-val',
            'X-Other' => 'other-val',
        ]);

        $response = $client->post('https://api.example.com', [
            'headers' => ['x-custom-header' => 'caller-val'],
        ]);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testConvenienceMethodsDelegateToRequest(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->exactly(4))
            ->method('request')
            ->willReturn(new Response('OK', 200));

        $client = new HeaderClient($mock, ['X-App' => 'Test']);

        $client->put('https://api.example.com');
        $client->patch('https://api.example.com');
        $client->delete('https://api.example.com');
        $client->get('https://api.example.com');
    }
}
