<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Security\CsrfMiddleware;
use Nqphp\Core\Security\CsrfTokenManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new CsrfMiddleware();
    }

    public function testSafeGetRequestPassesAndSetsCsrfCookie(): void
    {
        $request = Request::create('/safe-page', 'GET');
        $response = $this->middleware->process($request, fn($req) => new Response('OK'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
        $cookies = $response->headers->getCookies();
        $this->assertNotEmpty($cookies);
        $this->assertSame(CsrfTokenManager::COOKIE_NAME, $cookies[0]->getName());
    }

    public function testStateChangingPostWithoutTokenThrows403(): void
    {
        $request = Request::create('/submit', 'POST');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $this->middleware->process($request, fn($req) => new Response('OK'));
    }

    public function testStateChangingPostWithValidHeaderPasses(): void
    {
        $token = 'test-secret-token-12345';
        $request = Request::create('/submit', 'POST');
        $request->cookies->set(CsrfTokenManager::COOKIE_NAME, $token);
        $request->headers->set(CsrfTokenManager::HEADER_NAME, $token);

        $response = $this->middleware->process($request, fn($req) => new Response('Submitted'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Submitted', $response->getContent());
    }
}
