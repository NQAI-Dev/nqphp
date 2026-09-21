<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\JavaScriptResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class JavaScriptResponseTest extends TestCase
{
    public function testExtendsSymfonyResponse(): void
    {
        $response = new JavaScriptResponse("console.log('hello');");
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDefaultHeadersAndContent(): void
    {
        $code = 'function add(a, b) { return a + b; }';
        $response = new JavaScriptResponse($code);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/javascript; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame($code, $response->getContent());
    }

    public function testCustomStatusAndHeaders(): void
    {
        $response = new JavaScriptResponse(
            'window.__INIT__ = {};',
            201,
            ['X-Custom-Script' => 'analytics']
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/javascript; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('analytics', $response->headers->get('X-Custom-Script'));
        $this->assertSame('window.__INIT__ = {};', $response->getContent());
    }
}
