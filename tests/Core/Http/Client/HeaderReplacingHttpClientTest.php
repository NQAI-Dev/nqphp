<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HeaderReplacingHttpClient;
use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HeaderReplacingHttpClientTest extends TestCase
{
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
    }

    public function testImplementsHttpClientInterface(): void
    {
        $client = new HeaderReplacingHttpClient($this->mockClient, ['X-App-Version' => '2.0']);
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testReplacesExistingHeaderCaseInsensitively(): void
    {
        $this->mockClient->queueResponse(new Response('ok', 200));

        $client = new HeaderReplacingHttpClient($this->mockClient, [
            'Content-Type' => 'application/json',
            'X-Api-Key' => 'fixed-key',
        ]);

        $client->post('https://example.com/api', [
            'headers' => [
                'content-type' => 'text/plain',
                'X-API-KEY' => 'old-key',
                'Authorization' => 'Bearer token',
            ],
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $headers = $lastRequest['options']['headers'];

        $this->assertSame('application/json', $headers['Content-Type']);
        $this->assertSame('fixed-key', $headers['X-Api-Key']);
        $this->assertSame('Bearer token', $headers['Authorization']);
        $this->assertArrayNotHasKey('content-type', $headers);
        $this->assertArrayNotHasKey('X-API-KEY', $headers);
    }

    public function testAddsHeaderWhenNotPresent(): void
    {
        $this->mockClient->queueResponse(new Response('ok', 200));

        $client = new HeaderReplacingHttpClient($this->mockClient, [
            'X-Forwarded-Host' => 'nqphp.local',
        ]);

        $client->get('https://example.com/api');

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('nqphp.local', $lastRequest['options']['headers']['X-Forwarded-Host']);
    }
}
