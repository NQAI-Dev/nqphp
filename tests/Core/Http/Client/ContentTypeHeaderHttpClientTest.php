<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\ContentTypeHeaderHttpClient;
use Nqphp\Core\Http\Client\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ContentTypeHeaderHttpClientTest extends TestCase
{
    public function testSetsDefaultContentTypeWhenBodyIsPresent(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.example.com/items',
                $this->callback(function (array $options) {
                    return isset($options['headers']['Content-Type'])
                        && $options['headers']['Content-Type'] === 'application/json';
                })
            )
            ->willReturn(new Response('{"id":1}', 201));

        $client = new ContentTypeHeaderHttpClient($mock);
        $response = $client->post('https://api.example.com/items', ['body' => '{"name":"item"}']);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testDoesNotSetContentTypeWhenNoBodyPresent(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.example.com/items',
                $this->callback(function (array $options) {
                    return empty($options['headers']['Content-Type']);
                })
            )
            ->willReturn(new Response('[]', 200));

        $client = new ContentTypeHeaderHttpClient($mock);
        $response = $client->get('https://api.example.com/items');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPreservesExistingContentTypeHeader(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.example.com/upload',
                $this->callback(function (array $options) {
                    return isset($options['headers']['content-type'])
                        && $options['headers']['content-type'] === 'multipart/form-data';
                })
            )
            ->willReturn(new Response('OK', 200));

        $client = new ContentTypeHeaderHttpClient($mock, 'application/json');
        $response = $client->post('https://api.example.com/upload', [
            'body' => 'raw-data',
            'headers' => ['content-type' => 'multipart/form-data'],
        ]);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSetsCustomDefaultContentType(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'https://api.example.com/doc',
                $this->callback(function (array $options) {
                    return isset($options['headers']['Content-Type'])
                        && $options['headers']['Content-Type'] === 'application/xml';
                })
            )
            ->willReturn(new Response('<ok/>', 200));

        $client = new ContentTypeHeaderHttpClient($mock, 'application/xml');
        $response = $client->put('https://api.example.com/doc', ['body' => '<doc/>']);

        $this->assertSame(200, $response->getStatusCode());
    }
}
