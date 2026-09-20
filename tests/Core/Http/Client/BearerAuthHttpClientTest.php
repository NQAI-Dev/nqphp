<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\BearerAuthHttpClient;
use Nqphp\Core\Http\Client\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class BearerAuthHttpClientTest extends TestCase
{
    public function testRequestInjectsStaticBearerToken(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.example.com/me', $this->callback(function (array $options): bool {
                return isset($options['headers']['Authorization'])
                    && $options['headers']['Authorization'] === 'Bearer secret-token-123';
            }))
            ->willReturn(new Response('{"user":"alice"}', 200));

        $client = new BearerAuthHttpClient($mock, 'secret-token-123');
        $response = $client->get('https://api.example.com/me');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRequestResolvesTokenFromCallable(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api.example.com/items', $this->callback(function (array $options): bool {
                return isset($options['headers']['Authorization'])
                    && $options['headers']['Authorization'] === 'Bearer dynamic-jwt-456';
            }))
            ->willReturn(new Response('ok', 201));

        $counter = 0;
        $tokenProvider = function () use (&$counter): string {
            $counter++;
            return 'dynamic-jwt-456';
        };

        $client = new BearerAuthHttpClient($mock, $tokenProvider);
        $client->post('https://api.example.com/items');

        $this->assertSame(1, $counter);
    }

    public function testRequestDoesNotOverwriteExplicitAuthorizationHeader(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.example.com/custom', $this->callback(function (array $options): bool {
                return isset($options['headers']['Authorization'])
                    && $options['headers']['Authorization'] === 'Basic dXNlcjpwYXNz';
            }))
            ->willReturn(new Response('ok', 200));

        $client = new BearerAuthHttpClient($mock, 'default-bearer');
        $client->request('GET', 'https://api.example.com/custom', [
            'headers' => ['Authorization' => 'Basic dXNlcjpwYXNz'],
        ]);
    }
}
