<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\BaseUriHttpClient;
use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class BaseUriHttpClientTest extends TestCase
{
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
    }

    public function testImplementsHttpClientInterface(): void
    {
        $client = new BaseUriHttpClient($this->mockClient, 'https://api.example.com');
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testPrefixesRelativeUrls(): void
    {
        $this->mockClient->queueResponse(new Response('{"users":[]}', 200));
        $this->mockClient->queueResponse(new Response('{"users":[]}', 200));
        $client = new BaseUriHttpClient($this->mockClient, 'https://api.example.com/v1/');

        $response = $client->get('users');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('https://api.example.com/v1/users', $this->mockClient->getLastRequest()['url']);

        $responseWithLeadingSlash = $client->get('/users');
        $this->assertSame(200, $responseWithLeadingSlash->getStatusCode());
        $this->assertSame('https://api.example.com/v1/users', $this->mockClient->getLastRequest()['url']);
    }

    public function testPreservesAbsoluteUrls(): void
    {
        $this->mockClient->queueResponse(new Response('{"status":"ok"}', 200));
        $client = new BaseUriHttpClient($this->mockClient, 'https://api.example.com');

        $response = $client->get('https://other.example.org/health');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('https://other.example.org/health', $this->mockClient->getLastRequest()['url']);
    }

    public function testEmptyPathResolvesToBaseUri(): void
    {
        $this->mockClient->queueResponse(new Response('root', 200));
        $client = new BaseUriHttpClient($this->mockClient, 'https://api.example.com');

        $response = $client->get('');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('https://api.example.com', $this->mockClient->getLastRequest()['url']);
    }

    public function testAllHttpMethodsPrefixUrl(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->mockClient->queueResponse(new Response('ok', 201));
        }

        $client = new BaseUriHttpClient($this->mockClient, 'https://api.example.com');

        $resPost = $client->post('/resource');
        $this->assertSame(201, $resPost->getStatusCode());
        $this->assertSame('https://api.example.com/resource', $this->mockClient->getLastRequest()['url']);

        $resPut = $client->put('/resource');
        $this->assertSame(201, $resPut->getStatusCode());
        $this->assertSame('https://api.example.com/resource', $this->mockClient->getLastRequest()['url']);

        $resPatch = $client->patch('/resource');
        $this->assertSame(201, $resPatch->getStatusCode());
        $this->assertSame('https://api.example.com/resource', $this->mockClient->getLastRequest()['url']);

        $resDelete = $client->delete('/resource');
        $this->assertSame(201, $resDelete->getStatusCode());
        $this->assertSame('https://api.example.com/resource', $this->mockClient->getLastRequest()['url']);

        $resRequest = $client->request('OPTIONS', '/resource');
        $this->assertSame(201, $resRequest->getStatusCode());
        $this->assertSame('https://api.example.com/resource', $this->mockClient->getLastRequest()['url']);
    }
}
