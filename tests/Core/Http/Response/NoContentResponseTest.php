<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\NoContentResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class NoContentResponseTest extends TestCase
{
    public function testCreates204ResponseWithEmptyBody(): void
    {
        $response = new NoContentResponse();

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testAcceptsCustomHeaders(): void
    {
        $response = new NoContentResponse([
            'X-Custom-Header' => 'Value123',
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('Value123', $response->headers->get('X-Custom-Header'));
    }
}
