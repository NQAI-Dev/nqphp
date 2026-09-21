<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use InvalidArgumentException;
use Nqphp\Core\Http\Response\ImageResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ImageResponseTest extends TestCase
{
    public function testExtendsSymfonyResponse(): void
    {
        $response = new ImageResponse('binary', 'png');
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testSetsMimeTypeAndContentLengthByExtension(): void
    {
        $content = 'fake-png-binary';
        $response = new ImageResponse($content, 'png');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame((string) strlen($content), $response->headers->get('Content-Length'));
        $this->assertSame($content, $response->getContent());
    }

    public function testSupportsVariousFormats(): void
    {
        $this->assertSame('image/jpeg', (new ImageResponse('data', 'jpg'))->headers->get('Content-Type'));
        $this->assertSame('image/jpeg', (new ImageResponse('data', 'jpeg'))->headers->get('Content-Type'));
        $this->assertSame('image/gif', (new ImageResponse('data', 'gif'))->headers->get('Content-Type'));
        $this->assertSame('image/webp', (new ImageResponse('data', 'webp'))->headers->get('Content-Type'));
        $this->assertSame('image/svg+xml', (new ImageResponse('<svg></svg>', 'svg'))->headers->get('Content-Type'));
        $this->assertSame('image/x-icon', (new ImageResponse('data', 'ico'))->headers->get('Content-Type'));
    }

    public function testAcceptsFullMimeTypeDirectly(): void
    {
        $response = new ImageResponse('data', 'image/tiff');
        $this->assertSame('image/tiff', $response->headers->get('Content-Type'));
    }

    public function testThrowsOnUnsupportedFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Неподдерживаемый формат изображения: 'mp4'.");

        new ImageResponse('data', 'mp4');
    }
}
