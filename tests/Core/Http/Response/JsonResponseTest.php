<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use JsonException;
use Nqphp\Core\Http\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseTest extends TestCase
{
    public function testExtendsSymfonyResponse(): void
    {
        $response = new JsonResponse(['key' => 'value']);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDefaultJsonHeadersAndSerialization(): void
    {
        $data = ['success' => true, 'message' => 'Привет, мир!', 'url' => 'https://example.com/api'];
        $response = new JsonResponse($data);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('{"success":true,"message":"Привет, мир!","url":"https://example.com/api"}', $response->getContent());
    }

    public function testHandlesNullPayloadAsEmptyObject(): void
    {
        $response = new JsonResponse(null);
        $this->assertSame('{}', $response->getContent());
    }

    public function testCustomStatusAndHeaders(): void
    {
        $response = new JsonResponse(
            ['error' => 'not_found'],
            404,
            ['X-Error-Code' => 'RESOURCE_MISSING']
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('RESOURCE_MISSING', $response->headers->get('X-Error-Code'));
        $this->assertSame('{"error":"not_found"}', $response->getContent());
    }

    public function testThrowsOnInvalidJsonData(): void
    {
        $this->expectException(JsonException::class);
        new JsonResponse(NAN);
    }
}
