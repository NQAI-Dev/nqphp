<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\CssResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CssResponseTest extends TestCase
{
    public function testExtendsSymfonyResponse(): void
    {
        $response = new CssResponse('body { margin: 0; }');
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDefaultHeadersAndContent(): void
    {
        $css = 'body { background: #000; color: #fff; }';
        $response = new CssResponse($css);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/css; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame($css, $response->getContent());
    }

    public function testCustomStatusAndHeaders(): void
    {
        $response = new CssResponse(
            ':root { --brand: #00f; }',
            201,
            ['X-Theme' => 'dark']
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('text/css; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('dark', $response->headers->get('X-Theme'));
        $this->assertSame(':root { --brand: #00f; }', $response->getContent());
    }
}
