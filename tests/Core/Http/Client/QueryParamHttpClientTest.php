<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\MockHttpClient;
use Nqphp\Core\Http\Client\QueryParamHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class QueryParamHttpClientTest extends TestCase
{
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
    }

    public function testImplementsHttpClientInterface(): void
    {
        $client = new QueryParamHttpClient($this->mockClient, ['api_key' => 'secret']);
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testAppendsDefaultQueryParams(): void
    {
        $this->mockClient->queueResponse(new Response('ok', 200));
        $client = new QueryParamHttpClient($this->mockClient, ['api_key' => 'secret', 'version' => '1']);

        $client->get('https://api.example.com/data');
        $this->assertSame(
            'https://api.example.com/data?api_key=secret&version=1',
            $this->mockClient->getLastRequest()['url']
        );
    }

    public function testPreservesExistingQueryParamsAndAllowsOverride(): void
    {
        $this->mockClient->queueResponse(new Response('ok', 200));
        $client = new QueryParamHttpClient($this->mockClient, ['format' => 'json', 'limit' => 50]);

        $client->get('https://api.example.com/items?limit=10&page=2');
        $this->assertSame(
            'https://api.example.com/items?format=json&limit=10&page=2',
            $this->mockClient->getLastRequest()['url']
        );
    }

    public function testHandlesRelativeUrls(): void
    {
        $this->mockClient->queueResponse(new Response('ok', 200));
        $client = new QueryParamHttpClient($this->mockClient, ['token' => 'abc']);

        $client->post('/endpoint');
        $this->assertSame('/endpoint?token=abc', $this->mockClient->getLastRequest()['url']);
    }

    public function testAllHttpMethodsApplyParams(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->mockClient->queueResponse(new Response('ok', 200));
        }

        $client = new QueryParamHttpClient($this->mockClient, ['k' => 'v']);

        $client->put('/res');
        $this->assertSame('/res?k=v', $this->mockClient->getLastRequest()['url']);

        $client->patch('/res');
        $this->assertSame('/res?k=v', $this->mockClient->getLastRequest()['url']);

        $client->delete('/res');
        $this->assertSame('/res?k=v', $this->mockClient->getLastRequest()['url']);

        $client->request('OPTIONS', '/res');
        $this->assertSame('/res?k=v', $this->mockClient->getLastRequest()['url']);
    }
}
