<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\BasicAuthHttpClient;
use Nqphp\Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthHttpClientTest extends TestCase
{
    private MockHttpClient $mock;
    private BasicAuthHttpClient $client;

    protected function setUp(): void
    {
        $this->mock = new MockHttpClient();
        $this->client = new BasicAuthHttpClient($this->mock, 'admin', 'secret123');
    }

    public function testAddsBasicAuthHeaderToGet(): void
    {
        $this->mock->queueResponse(new Response('OK', 200));

        $response = $this->client->get('https://api.example.com/data');
        $this->assertSame(200, $response->getStatusCode());

        $request = $this->mock->getLastRequest();
        $this->assertNotNull($request);
        $expectedHeader = 'Basic ' . base64_encode('admin:secret123');
        $this->assertSame($expectedHeader, $request['options']['headers']['Authorization'] ?? null);
    }

    public function testAddsBasicAuthHeaderToPost(): void
    {
        $this->mock->queueResponse(new Response('Created', 201));

        $this->client->post('https://api.example.com/items', ['json' => ['name' => 'test']]);

        $request = $this->mock->getLastRequest();
        $this->assertNotNull($request);
        $expectedHeader = 'Basic ' . base64_encode('admin:secret123');
        $this->assertSame($expectedHeader, $request['options']['headers']['Authorization'] ?? null);
    }

    public function testPreservesExplicitAuthorizationHeader(): void
    {
        $this->mock->queueResponse(new Response('OK', 200));

        $this->client->get('https://api.example.com/custom', [
            'headers' => ['Authorization' => 'Bearer existing-token-xyz'],
        ]);

        $request = $this->mock->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame('Bearer existing-token-xyz', $request['options']['headers']['Authorization']);
    }
}
