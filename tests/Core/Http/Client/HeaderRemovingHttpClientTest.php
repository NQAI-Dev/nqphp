<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HeaderRemovingHttpClient;
use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HeaderRemovingHttpClientTest extends TestCase
{
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
    }

    public function testImplementsHttpClientInterface(): void
    {
        $client = new HeaderRemovingHttpClient($this->mockClient, ['Authorization']);
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testStripsSpecifiedHeadersCaseInsensitively(): void
    {
        $this->mockClient->queueResponse(new Response('ok', 200));

        $client = new HeaderRemovingHttpClient($this->mockClient, ['authorization', 'x-secret-key']);
        $client->get('https://example.com/api', [
            'headers' => [
                'Authorization' => 'Bearer token',
                'X-SECRET-KEY' => 'secret',
                'Accept' => 'application/json',
            ],
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertArrayHasKey('Accept', $lastRequest['options']['headers']);
        $this->assertArrayNotHasKey('Authorization', $lastRequest['options']['headers']);
        $this->assertArrayNotHasKey('X-SECRET-KEY', $lastRequest['options']['headers']);
    }

    public function testWorksWhenNoHeadersProvided(): void
    {
        $this->mockClient->queueResponse(new Response('created', 201));

        $client = new HeaderRemovingHttpClient($this->mockClient, ['Cookie']);
        $response = $client->post('https://example.com/api', ['body' => 'data']);

        $this->assertSame(201, $response->getStatusCode());
        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('data', $lastRequest['options']['body']);
    }
}
