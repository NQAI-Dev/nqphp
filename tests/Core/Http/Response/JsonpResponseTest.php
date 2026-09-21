<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use InvalidArgumentException;
use Nqphp\Core\Http\Response\JsonpResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class JsonpResponseTest extends TestCase
{
    public function testExtendsSymfonyResponse(): void
    {
        $response = new JsonpResponse('handleResponse', ['ok' => true]);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRendersValidJsonpWithHeaders(): void
    {
        $response = new JsonpResponse('myCallback', ['user' => 'Alex', 'id' => 10]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/javascript; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('/**/myCallback({"user":"Alex","id":10});', $response->getContent());
    }

    public function testAcceptsNestedCallbackIdentities(): void
    {
        $response = new JsonpResponse('App.callbacks._onReady', ['status' => 'ready']);
        $this->assertSame('/**/App.callbacks._onReady({"status":"ready"});', $response->getContent());
    }

    public function testRendersEmptyObjectWhenDataIsNull(): void
    {
        $response = new JsonpResponse('cb', null);
        $this->assertSame('/**/cb({});', $response->getContent());
    }

    public function testThrowsOnInvalidCallbackName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Недопустимое имя callback для JSONP: 'alert(1);foo'.");

        new JsonpResponse('alert(1);foo', ['test' => 1]);
    }
}
