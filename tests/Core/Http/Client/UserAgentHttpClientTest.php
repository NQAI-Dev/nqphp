<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\MockHttpClient;
use Nqphp\Core\Http\Client\UserAgentHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class UserAgentHttpClientTest extends TestCase
{
    private MockHttpClient $mock;

    protected function setUp(): void
    {
        $this->mock = new MockHttpClient();
    }

    public function testImplementsHttpClientInterface(): void
    {
        $client = new UserAgentHttpClient($this->mock, 'CustomBot/1.0');
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testAddsUserAgentWhenMissing(): void
    {
        $this->mock->queueResponse(new Response('OK', 200));

        $client = new UserAgentHttpClient($this->mock, 'CustomBot/1.0');
        $client->get('https://example.com/api');

        $requests = $this->mock->getRequests();
        $this->assertCount(1, $requests);
        $this->assertSame('CustomBot/1.0', $requests[0]['options']['headers']['User-Agent']);
    }

    public function testPreservesExistingUserAgent(): void
    {
        $this->mock->queueResponse(new Response('OK', 200));

        $client = new UserAgentHttpClient($this->mock, 'CustomBot/1.0');
        $client->get('https://example.com/api', [
            'headers' => ['user-agent' => 'ExistingAgent/2.0'],
        ]);

        $requests = $this->mock->getRequests();
        $this->assertCount(1, $requests);
        $this->assertSame('ExistingAgent/2.0', $requests[0]['options']['headers']['user-agent']);
        $this->assertArrayNotHasKey('User-Agent', $requests[0]['options']['headers']);
    }

    public function testConvenienceMethods(): void
    {
        $this->mock->queueResponse(new Response('OK', 200));
        $this->mock->queueResponse(new Response('OK', 200));
        $this->mock->queueResponse(new Response('OK', 200));
        $this->mock->queueResponse(new Response('OK', 200));

        $client = new UserAgentHttpClient($this->mock, 'CustomBot/1.0');
        $client->post('https://example.com/post', ['body' => 'data']);
        $client->put('https://example.com/put', ['body' => 'data']);
        $client->patch('https://example.com/patch', ['body' => 'data']);
        $client->delete('https://example.com/delete');

        $requests = $this->mock->getRequests();
        $this->assertCount(4, $requests);
        $this->assertSame('POST', $requests[0]['method']);
        $this->assertSame('PUT', $requests[1]['method']);
        $this->assertSame('PATCH', $requests[2]['method']);
        $this->assertSame('DELETE', $requests[3]['method']);
        $this->assertSame('CustomBot/1.0', $requests[0]['options']['headers']['User-Agent']);
        $this->assertSame('CustomBot/1.0', $requests[1]['options']['headers']['User-Agent']);
        $this->assertSame('CustomBot/1.0', $requests[2]['options']['headers']['User-Agent']);
        $this->assertSame('CustomBot/1.0', $requests[3]['options']['headers']['User-Agent']);
    }
}
