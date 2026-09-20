<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Middleware;

use Nqphp\Core\Middleware\CorsMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddlewareTest extends TestCase
{
    public function testHandlesPreflightOptionsRequest(): void
    {
        $middleware = new CorsMiddleware(
            allowedOrigins: ['https://example.com'],
            allowedMethods: ['GET', 'POST'],
            allowedHeaders: ['Content-Type', 'Authorization'],
            maxAge: 3600
        );

        $request = Request::create('/api/resource', 'OPTIONS');
        $request->headers->set('Origin', 'https://example.com');
        $request->headers->set('Access-Control-Request-Method', 'POST');

        $response = $middleware->process($request, function () {
            $this->fail('Next handler should not be called for preflight OPTIONS');
        });

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('https://example.com', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertSame('Content-Type, Authorization', $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertSame('3600', $response->headers->get('Access-Control-Max-Age'));
    }

    public function testWildcardOriginWithoutCredentials(): void
    {
        $middleware = new CorsMiddleware(allowedOrigins: ['*']);

        $request = Request::create('/api/data', 'GET');
        $request->headers->set('Origin', 'https://foo.bar');

        $response = $middleware->process($request, fn () => new Response('data', Response::HTTP_OK));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertFalse($response->headers->has('Access-Control-Allow-Credentials'));
    }

    public function testWildcardOriginWithCredentialsReflectsOrigin(): void
    {
        $middleware = new CorsMiddleware(
            allowedOrigins: ['*'],
            allowCredentials: true
        );

        $request = Request::create('/api/me', 'GET');
        $request->headers->set('Origin', 'https://trusted.app');

        $response = $middleware->process($request, fn () => new Response('auth_data', Response::HTTP_OK));

        $this->assertSame('https://trusted.app', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
    }

    public function testDisallowedOriginGetsNoCorsHeaders(): void
    {
        $middleware = new CorsMiddleware(allowedOrigins: ['https://allowed.com']);

        $request = Request::create('/api/info', 'GET');
        $request->headers->set('Origin', 'https://evil.com');

        $response = $middleware->process($request, fn () => new Response('ok', Response::HTTP_OK));

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testExposedHeadersApplied(): void
    {
        $middleware = new CorsMiddleware(
            allowedOrigins: ['*'],
            exposedHeaders: ['X-Total-Count', 'X-Request-Id']
        );

        $request = Request::create('/api/items', 'GET');
        $request->headers->set('Origin', 'https://client.test');

        $response = $middleware->process($request, fn () => new Response('[]', Response::HTTP_OK));

        $this->assertSame('X-Total-Count, X-Request-Id', $response->headers->get('Access-Control-Expose-Headers'));
    }
}
