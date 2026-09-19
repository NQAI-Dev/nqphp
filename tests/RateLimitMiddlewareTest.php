<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Cache\ArrayCache;
use Nqphp\Core\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RateLimitMiddlewareTest extends TestCase
{
    public function testAllowsRequestsWithinLimit(): void
    {
        $cache = new ArrayCache();
        $middleware = new RateLimitMiddleware($cache, 2, 60);

        $request = Request::create('/');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $next = function () {
            return new Response('OK');
        };

        // Request 1
        $response1 = $middleware->process($request, $next);
        self::assertSame(200, $response1->getStatusCode());

        // Request 2
        $response2 = $middleware->process($request, $next);
        self::assertSame(200, $response2->getStatusCode());
    }

    public function testBlocksRequestsOverLimit(): void
    {
        $cache = new ArrayCache();
        $middleware = new RateLimitMiddleware($cache, 2, 60);

        $request = Request::create('/');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $next = function () {
            return new Response('OK');
        };

        $middleware->process($request, $next); // 1
        $middleware->process($request, $next); // 2

        // Request 3
        $response3 = $middleware->process($request, $next);
        self::assertSame(429, $response3->getStatusCode());
        self::assertSame('Too Many Requests', $response3->getContent());
    }

    public function testAllowsAfterWindowExpiry(): void
    {
        $cache = new ArrayCache();
        $middleware = new RateLimitMiddleware($cache, 1, 1);

        $request = Request::create('/');
        $request->server->set('REMOTE_ADDR', '192.168.1.2');

        $next = function () {
            return new Response('OK');
        };

        $response1 = $middleware->process($request, $next);
        self::assertSame(200, $response1->getStatusCode());

        // Force expiry by manipulating the cache manually (mocking time passage)
        $ref = new \ReflectionProperty(ArrayCache::class, 'entries');
        $ref->setAccessible(true);
        $entries = $ref->getValue($cache);
        $entries['rate_limit:192.168.1.2']['expires'] = time() - 10;
        $ref->setValue($cache, $entries);

        $response2 = $middleware->process($request, $next);
        self::assertSame(200, $response2->getStatusCode());
    }

    public function testIsolatesDifferentIPs(): void
    {
        $cache = new ArrayCache();
        $middleware = new RateLimitMiddleware($cache, 1, 60);

        $next = function () {
            return new Response('OK');
        };

        $request1 = Request::create('/');
        $request1->server->set('REMOTE_ADDR', '1.1.1.1');

        $request2 = Request::create('/');
        $request2->server->set('REMOTE_ADDR', '2.2.2.2');

        // IP 1 fills its quota
        $middleware->process($request1, $next);
        $responseBlocked = $middleware->process($request1, $next);
        self::assertSame(429, $responseBlocked->getStatusCode());

        // IP 2 is unaffected
        $responseOk = $middleware->process($request2, $next);
        self::assertSame(200, $responseOk->getStatusCode());
    }
}
