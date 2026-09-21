<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HttpClientInterface;
use Nqphp\Core\Http\Client\RateLimitExceededException;
use Nqphp\Core\Http\Client\RateLimitingHttpClient;
use Nqphp\Core\Http\RateLimit\InMemoryRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class RateLimitingHttpClientTest extends TestCase
{
    public function testAllowsRequestsUnderTheLimit(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->exactly(3))
            ->method('request')
            ->willReturn(new Response('ok', 200));

        $client = new RateLimitingHttpClient(
            $mock,
            new InMemoryRateLimiter(),
            maxAttempts: 3,
            decaySeconds: 60
        );

        $response1 = $client->get('https://api.example.com/data');
        $response2 = $client->get('https://api.example.com/data');
        $response3 = $client->get('https://api.example.com/data');

        $this->assertSame(200, $response1->getStatusCode());
        $this->assertSame(200, $response2->getStatusCode());
        $this->assertSame(200, $response3->getStatusCode());
    }

    public function testThrowsWhenLimitIsExceeded(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->exactly(2))
            ->method('request')
            ->willReturn(new Response('ok', 200));

        $client = new RateLimitingHttpClient(
            $mock,
            new InMemoryRateLimiter(),
            maxAttempts: 2,
            decaySeconds: 60
        );

        $client->get('https://api.example.com/data');
        $client->get('https://api.example.com/data');

        try {
            $client->get('https://api.example.com/data');
            $this->fail('Expected RateLimitExceededException');
        } catch (RateLimitExceededException $e) {
            $this->assertGreaterThan(0, $e->retryAfter);
            $this->assertStringContainsString('Rate limit exceeded', $e->getMessage());
        }
    }

    public function testLimitsArePerHost(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->exactly(2))
            ->method('request')
            ->willReturn(new Response('ok', 200));

        $client = new RateLimitingHttpClient(
            $mock,
            new InMemoryRateLimiter(),
            maxAttempts: 1,
            decaySeconds: 60
        );

        $client->get('https://api-one.example.com/data');
        $client->get('https://api-two.example.com/data');

        $this->expectException(RateLimitExceededException::class);
        $client->get('https://api-one.example.com/other');
    }

    public function testCustomKeyOverridesHostDerivation(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->willReturn(new Response('ok', 200));

        $limiter = new InMemoryRateLimiter();
        $client = new RateLimitingHttpClient(
            $mock,
            $limiter,
            maxAttempts: 1,
            decaySeconds: 60,
            key: 'global-key'
        );

        $client->get('https://api-one.example.com/data');

        $this->expectException(RateLimitExceededException::class);
        $client->get('https://api-two.example.com/data');
    }
}
