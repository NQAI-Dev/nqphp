<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HeaderAppendingHttpClient;
use Nqphp\Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HeaderAppendingHttpClientTest extends TestCase
{
    public function testAppendsDefaultHeaders(): void
    {
        $mock = new MockHttpClient([
            function (string $method, string $url, array $options): Response {
                $this->assertSame('MyApiClient/1.0', $options['headers']['User-Agent'] ?? null);
                $this->assertSame('secret-token', $options['headers']['X-Api-Key'] ?? null);
                $this->assertSame('application/json', $options['headers']['Accept'] ?? null);
                return new Response('ok', 200);
            }
        ]);

        $client = new HeaderAppendingHttpClient($mock, [
            'User-Agent' => 'MyApiClient/1.0',
            'X-Api-Key' => 'secret-token',
            'Accept' => 'application/json',
        ]);

        $res = $client->get('https://example.com/api/test');
        $this->assertSame(200, $res->getStatusCode());
    }

    public function testRequestOptionsOverrideDefaultHeadersCaseInsensitive(): void
    {
        $mock = new MockHttpClient([
            function (string $method, string $url, array $options): Response {
                $this->assertSame('CustomUserAgent/2.0', $options['headers']['user-agent'] ?? null);
                $this->assertArrayNotHasKey('User-Agent', $options['headers']);
                $this->assertSame('secret-token', $options['headers']['X-Api-Key'] ?? null);
                return new Response('ok', 200);
            }
        ]);

        $client = new HeaderAppendingHttpClient($mock, [
            'User-Agent' => 'DefaultAgent/1.0',
            'X-Api-Key' => 'secret-token',
        ]);

        $res = $client->post('https://example.com/api/test', [
            'headers' => [
                'user-agent' => 'CustomUserAgent/2.0',
            ],
        ]);

        $this->assertSame(200, $res->getStatusCode());
    }

    public function testConvenienceMethods(): void
    {
        $recorded = [];
        $mock = new MockHttpClient([
            function (string $method, string $url, array $options) use (&$recorded): Response {
                $recorded[] = [$method, $url, $options['headers'] ?? []];
                return new Response('ok', 200);
            },
            function (string $method, string $url, array $options) use (&$recorded): Response {
                $recorded[] = [$method, $url, $options['headers'] ?? []];
                return new Response('ok', 200);
            },
            function (string $method, string $url, array $options) use (&$recorded): Response {
                $recorded[] = [$method, $url, $options['headers'] ?? []];
                return new Response('ok', 200);
            },
        ]);

        $client = new HeaderAppendingHttpClient($mock, ['X-Global' => 'true']);

        $client->put('https://example.com/1');
        $client->patch('https://example.com/2');
        $client->delete('https://example.com/3');

        $this->assertCount(3, $recorded);
        $this->assertSame('PUT', $recorded[0][0]);
        $this->assertSame('true', $recorded[0][2]['X-Global']);
        $this->assertSame('PATCH', $recorded[1][0]);
        $this->assertSame('true', $recorded[1][2]['X-Global']);
        $this->assertSame('DELETE', $recorded[2][0]);
        $this->assertSame('true', $recorded[2][2]['X-Global']);
    }
}
