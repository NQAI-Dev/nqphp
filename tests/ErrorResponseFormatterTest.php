<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Http\ErrorResponseFormatter;
use Nqphp\Core\Middleware\ErrorHandlerMiddleware;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ErrorResponseFormatterTest extends TestCase
{
    public function testFormatsHttpExceptionAsProblemJson(): void
    {
        $request = Request::create('/widgets/42?view=full');
        $request->headers->set('Accept', 'application/problem+json');

        $response = (new ErrorResponseFormatter())->format(
            $request,
            new HttpException(422, 'Widget is invalid'),
        );

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        self::assertSame('Accept', $response->headers->get('Vary'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame([
            'type' => 'about:blank',
            'title' => 'Unprocessable Content',
            'status' => 422,
            'detail' => 'Widget is invalid',
            'instance' => '/widgets/42?view=full',
        ], json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testNegotiatesVendorJsonUsingQualityValues(): void
    {
        $request = Request::create('/api');
        $request->headers->set('Accept', 'text/html;q=0.2, application/vnd.nqphp.error+json;q=0.9');

        $response = (new ErrorResponseFormatter())->format($request, new HttpException(404, 'Missing'));

        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        self::assertSame(404, json_decode((string) $response->getContent(), true)['status']);
    }

    public function testFormatsAndEscapesHtmlResponse(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept', 'text/html');

        $response = (new ErrorResponseFormatter())->format(
            $request,
            new HttpException(400, '<script>alert("x")</script>'),
        );

        self::assertStringContainsString('<h1>400 Bad Request</h1>', (string) $response->getContent());
        self::assertStringContainsString('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', (string) $response->getContent());
        self::assertStringNotContainsString('<script>', (string) $response->getContent());
    }

    public function testHidesUnexpectedExceptionDetailsOutsideDebugMode(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept', 'application/json');

        $response = (new ErrorResponseFormatter())->format($request, new RuntimeException('database password leaked'));
        $problem = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('Internal Server Error', $problem['detail']);
        self::assertArrayNotHasKey('exception', $problem);
    }

    public function testDebugModeIncludesUnexpectedExceptionDetailsAndClass(): void
    {
        $request = Request::create('/debug');
        $request->headers->set('Accept', 'application/json');

        $response = (new ErrorResponseFormatter(debug: true))->format($request, new RuntimeException('Boom'));
        $problem = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('Boom', $problem['detail']);
        self::assertSame(RuntimeException::class, $problem['exception']);
    }

    public function testInvalidHttpStatusFallsBackToInternalServerError(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept', 'text/plain');

        $response = (new ErrorResponseFormatter())->format(
            $request,
            new HttpException(200, 'Invalid status'),
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('Invalid status', $response->getContent());
    }

    public function testMiddlewareDelegatesExceptionFormatting(): void
    {
        $request = Request::create('/middleware');
        $request->headers->set('Accept', 'application/json');
        $middleware = new ErrorHandlerMiddleware();

        $response = $middleware->process($request, static function (): Response {
            throw new HttpException(403, 'Forbidden area');
        });

        $problem = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('Forbidden area', $problem['detail']);
    }

    public function testMiddlewareReturnsSuccessfulResponseUntouched(): void
    {
        $expected = new Response('ok', 201);
        $response = (new ErrorHandlerMiddleware())->process(
            Request::create('/'),
            static fn (): Response => $expected,
        );

        self::assertSame($expected, $response);
    }
}
