<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HttpClientException;
use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\JsonResponseHttpClient;
use Nqphp\Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseHttpClientTest extends TestCase
{
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
    }

    public function testImplementsHttpClientInterface(): void
    {
        $client = new JsonResponseHttpClient($this->mockClient);
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testSetsAcceptHeaderByDefault(): void
    {
        $this->mockClient->queueResponse(new Response('{"status":"ok"}', 200));

        $client = new JsonResponseHttpClient($this->mockClient);
        $response = $client->get('https://api.example.com/status');

        $this->assertSame(200, $response->getStatusCode());
        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('application/json', $lastRequest['options']['headers']['Accept']);
    }

    public function testPreservesExistingAcceptHeader(): void
    {
        $this->mockClient->queueResponse(new Response('{"status":"ok"}', 200));

        $client = new JsonResponseHttpClient($this->mockClient);
        $client->get('https://api.example.com/status', [
            'headers' => ['Accept' => 'application/vnd.custom+json'],
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('application/vnd.custom+json', $lastRequest['options']['headers']['Accept']);
    }

    public function testAllowsEmptyResponse(): void
    {
        $this->mockClient->queueResponse(new Response('', 204));

        $client = new JsonResponseHttpClient($this->mockClient);
        $response = $client->delete('https://api.example.com/items/1');

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testThrowsExceptionOnInvalidJson(): void
    {
        $this->mockClient->queueResponse(new Response('<html>Error</html>', 500));

        $client = new JsonResponseHttpClient($this->mockClient);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Ответ от https://api.example.com/fail не является валидным JSON');

        $client->get('https://api.example.com/fail');
    }
}
