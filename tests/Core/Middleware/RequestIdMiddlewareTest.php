<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Middleware;

use Nqphp\Core\Middleware\RequestIdMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddlewareTest extends TestCase
{
    public function testGeneratesNewUuidWhenHeaderMissing(): void
    {
        $middleware = new RequestIdMiddleware();
        $request = Request::create('/test');

        $capturedRequestId = null;
        $response = $middleware->process($request, function (Request $req) use (&$capturedRequestId): Response {
            $capturedRequestId = $req->attributes->get(RequestIdMiddleware::ATTRIBUTE_NAME);
            return new Response('OK');
        });

        $this->assertNotNull($capturedRequestId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $capturedRequestId
        );
        $this->assertSame($capturedRequestId, $response->headers->get(RequestIdMiddleware::HEADER_NAME));
    }

    public function testPreservesExistingIncomingRequestId(): void
    {
        $middleware = new RequestIdMiddleware();
        $request = Request::create('/test');
        $request->headers->set('X-Request-ID', 'custom-trace-id-12345');

        $capturedRequestId = null;
        $response = $middleware->process($request, function (Request $req) use (&$capturedRequestId): Response {
            $capturedRequestId = $req->attributes->get(RequestIdMiddleware::ATTRIBUTE_NAME);
            return new Response('OK');
        });

        $this->assertSame('custom-trace-id-12345', $capturedRequestId);
        $this->assertSame('custom-trace-id-12345', $response->headers->get('X-Request-ID'));
    }

    public function testCustomHeaderAndAttributeName(): void
    {
        $middleware = new RequestIdMiddleware('X-Correlation-ID', 'trace_id');
        $request = Request::create('/test');
        $request->headers->set('X-Correlation-ID', 'client-corr-99');

        $capturedTraceId = null;
        $response = $middleware->process($request, function (Request $req) use (&$capturedTraceId): Response {
            $capturedTraceId = $req->attributes->get('trace_id');
            return new Response('OK');
        });

        $this->assertSame('client-corr-99', $capturedTraceId);
        $this->assertSame('client-corr-99', $response->headers->get('X-Correlation-ID'));
    }
}
