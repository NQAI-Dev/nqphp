<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\HtmlResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HtmlResponseTest extends TestCase
{
    public function testDefaultContentTypeAndStatus(): void
    {
        $response = new HtmlResponse('<h1>Hello World</h1>');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('<h1>Hello World</h1>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testCustomStatusAndHeaders(): void
    {
        $response = new HtmlResponse('<p>Not Found</p>', 404, [
            'X-Custom' => 'Foo',
        ]);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('<p>Not Found</p>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('Foo', $response->headers->get('X-Custom'));
    }

    public function testPreservesExplicitContentType(): void
    {
        $response = new HtmlResponse('<span>custom</span>', 200, [
            'Content-Type' => 'text/html; charset=windows-1251',
        ]);

        $this->assertSame('text/html; charset=windows-1251', $response->headers->get('Content-Type'));
    }
}
