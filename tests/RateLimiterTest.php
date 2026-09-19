<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Http\RateLimit\InMemoryRateLimiter;
use Nqphp\Core\Middleware\RateLimiterMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RateLimiterTest extends TestCase
{
    public function testInMemoryRateLimiterHitsAndDecay(): void
    {
        $limiter = new InMemoryRateLimiter();

        $res1 = $limiter->hit('user_1', 2, 10);
        $this->assertTrue($res1['allowed']);
        $this->assertSame(1, $res1['remaining']);

        $res2 = $limiter->hit('user_1', 2, 10);
        $this->assertTrue($res2['allowed']);
        $this->assertSame(0, $res2['remaining']);

        $res3 = $limiter->hit('user_1', 2, 10);
        $this->assertFalse($res3['allowed']);
        $this->assertSame(0, $res3['remaining']);
        $this->assertGreaterThanOrEqual(1, $res3['retry_after']);

        $limiter->reset('user_1');
        $resAfterReset = $limiter->hit('user_1', 2, 10);
        $this->assertTrue($resAfterReset['allowed']);
    }

    public function testRateLimiterMiddlewareBlocksWhenLimitExceeded(): void
    {
        $limiter = new InMemoryRateLimiter();
        $middleware = new RateLimiterMiddleware($limiter, maxAttempts: 2, decaySeconds: 60);

        $request = Request::create('/api/test', 'GET', server: ['REMOTE_ADDR' => '192.168.1.50']);
        $next = fn(Request $req): Response => new Response('ok', Response::HTTP_OK);

        // First hit -> 200
        $response1 = $middleware->process($request, $next);
        $this->assertSame(Response::HTTP_OK, $response1->getStatusCode());
        $this->assertSame('1', $response1->headers->get('X-RateLimit-Remaining'));

        // Second hit -> 200
        $response2 = $middleware->process($request, $next);
        $this->assertSame(Response::HTTP_OK, $response2->getStatusCode());
        $this->assertSame('0', $response2->headers->get('X-RateLimit-Remaining'));

        // Third hit -> 429 Too Many Requests (RFC 7807 problem details)
        $response3 = $middleware->process($request, $next);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response3->getStatusCode());
        $this->assertSame('application/problem+json', $response3->headers->get('Content-Type'));
        $this->assertTrue($response3->headers->has('Retry-After'));

        $body = json_decode((string) $response3->getContent(), true);
        $this->assertSame(429, $body['status']);
        $this->assertSame('Too Many Requests', $body['title']);
    }
}
