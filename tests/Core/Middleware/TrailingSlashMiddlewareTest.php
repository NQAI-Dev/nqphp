<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Middleware;

use Nqphp\Core\Middleware\TrailingSlashMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TrailingSlashMiddlewareTest extends TestCase
{
    public function testRedirectsUrlsWithTrailingSlash(): void
    {
        $middleware = new TrailingSlashMiddleware();
        $request = Request::create('https://example.com/api/users/');

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new Response('OK');
        };

        $response = $middleware->process($request, $next);

        $this->assertFalse($nextCalled);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/api/users', $response->getTargetUrl());
    }

    public function testPreservesQueryParametersOnRedirect(): void
    {
        $middleware = new TrailingSlashMiddleware(308);
        $request = Request::create('https://example.com/posts/?page=2&sort=asc');

        $next = fn () => new Response('OK');
        $response = $middleware->process($request, $next);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(308, $response->getStatusCode());
        $this->assertSame('/posts?page=2&sort=asc', $response->getTargetUrl());
    }

    public function testDoesNotRedirectRootPath(): void
    {
        $middleware = new TrailingSlashMiddleware();
        $request = Request::create('https://example.com/');

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new Response('ROOT');
        };

        $response = $middleware->process($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertSame('ROOT', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPassesThroughUrlsWithoutTrailingSlash(): void
    {
        $middleware = new TrailingSlashMiddleware();
        $request = Request::create('https://example.com/api/users');

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new Response('PASS');
        };

        $response = $middleware->process($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertSame('PASS', $response->getContent());
    }
}
