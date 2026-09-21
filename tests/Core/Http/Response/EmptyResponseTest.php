<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\EmptyResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class EmptyResponseTest extends TestCase
{
    public function testExtendsSymfonyResponse(): void
    {
        $response = new EmptyResponse();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDefaultsTo204NoContentWithEmptyBody(): void
    {
        $response = new EmptyResponse();
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testCustomStatusAndHeaders(): void
    {
        $response = new EmptyResponse(201, ['X-Custom-Header' => 'value']);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertSame('value', $response->headers->get('X-Custom-Header'));
    }
}
