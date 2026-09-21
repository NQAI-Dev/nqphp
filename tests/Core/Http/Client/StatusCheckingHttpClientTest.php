<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HttpClientException;
use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\MockHttpClient;
use Nqphp\Core\Http\Client\StatusCheckingHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class StatusCheckingHttpClientTest extends TestCase
{
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
    }

    public function testImplementsHttpClientInterface(): void
    {
        $client = new StatusCheckingHttpClient($this->mockClient);
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testAllowsSuccessfulResponses(): void
    {
        $this->mockClient->queueResponse(new Response('ok', 200));

        $client = new StatusCheckingHttpClient($this->mockClient);
        $response = $client->get('https://example.com/api');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testThrowsOn4xxByDefault(): void
    {
        $this->mockClient->queueResponse(new Response('Not Found', 404));

        $client = new StatusCheckingHttpClient($this->mockClient);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('HTTP-запрос GET https://example.com/not-found завершился клиентской ошибкой 404.');

        $client->get('https://example.com/not-found');
    }

    public function testThrowsOn5xxByDefault(): void
    {
        $this->mockClient->queueResponse(new Response('Server Error', 500));

        $client = new StatusCheckingHttpClient($this->mockClient);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('HTTP-запрос POST /submit завершился серверной ошибкой 500.');

        $client->post('/submit');
    }

    public function testAllowsIgnoringClientErrors(): void
    {
        $this->mockClient->queueResponse(new Response('Unprocessable', 422));

        $client = new StatusCheckingHttpClient($this->mockClient, throwOnClientError: false);
        $response = $client->post('/validate');

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testAllowsIgnoringServerErrors(): void
    {
        $this->mockClient->queueResponse(new Response('Bad Gateway', 502));

        $client = new StatusCheckingHttpClient($this->mockClient, throwOnServerError: false);
        $response = $client->get('/upstream');

        $this->assertSame(502, $response->getStatusCode());
    }
}
