<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HeaderCheckingHttpClient;
use Nqphp\Core\Http\Client\HttpClientException;
use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HeaderCheckingHttpClientTest extends TestCase
{
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
    }

    public function testImplementsHttpClientInterface(): void
    {
        $client = new HeaderCheckingHttpClient($this->mockClient, ['Content-Type']);
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testPassesWhenRequiredHeadersPresent(): void
    {
        $this->mockClient->queueResponse(new Response('ok', 200, [
            'Content-Type' => 'application/json',
            'X-Request-Id' => '12345',
        ]));

        $client = new HeaderCheckingHttpClient($this->mockClient, ['Content-Type', 'X-Request-Id']);
        $response = $client->get('https://example.com/api');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testThrowsExceptionWhenRequiredHeaderMissing(): void
    {
        $this->mockClient->queueResponse(new Response('ok', 200, [
            'Content-Type' => 'application/json',
        ]));

        $client = new HeaderCheckingHttpClient($this->mockClient, ['Content-Type', 'X-Required-Token']);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Ответ от https://example.com/api не содержит обязательного заголовка: X-Required-Token');

        $client->get('https://example.com/api');
    }

    public function testValidatesOnOtherMethods(): void
    {
        $this->mockClient->queueResponse(new Response('created', 201));

        $client = new HeaderCheckingHttpClient($this->mockClient, ['Location']);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Ответ от /items не содержит обязательного заголовка: Location');

        $client->post('/items');
    }
}
