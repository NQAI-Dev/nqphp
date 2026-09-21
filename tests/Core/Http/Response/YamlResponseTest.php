<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\YamlResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class YamlResponseTest extends TestCase
{
    public function testExtendsSymfonyResponse(): void
    {
        $response = new YamlResponse(['key' => 'value']);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDefaultHeadersAndContent(): void
    {
        $data = ['app' => ['name' => 'nqphp', 'debug' => true]];
        $response = new YamlResponse($data);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/x-yaml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('name: nqphp', $response->getContent());
        $this->assertStringContainsString('debug: true', $response->getContent());
    }

    public function testHandlesNullPayload(): void
    {
        $response = new YamlResponse(null);
        $this->assertSame("--- {}\n", $response->getContent());
    }

    public function testCustomStatusAndHeaders(): void
    {
        $response = new YamlResponse(
            ['status' => 'created'],
            201,
            ['X-Format' => 'yaml']
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/x-yaml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('yaml', $response->headers->get('X-Format'));
    }
}
