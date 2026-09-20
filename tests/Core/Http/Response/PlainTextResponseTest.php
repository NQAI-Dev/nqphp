<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\PlainTextResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PlainTextResponseTest extends TestCase
{
    public function testDefaultContentTypeAndStatus(): void
    {
        $response = new PlainTextResponse('Hello World');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Hello World', $response->getContent());
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testCustomStatusAndHeaders(): void
    {
        $response = new PlainTextResponse('Not Found', 404, [
            'X-Custom' => 'Foo',
        ]);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getContent());
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('Foo', $response->headers->get('X-Custom'));
    }

    public function testPreservesExplicitContentType(): void
    {
        $response = new PlainTextResponse('custom', 200, [
            'Content-Type' => 'text/plain; charset=iso-8859-1',
        ]);

        $this->assertSame('text/plain; charset=iso-8859-1', $response->headers->get('Content-Type'));
    }
}
