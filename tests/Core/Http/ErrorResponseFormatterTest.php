<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http;

use Exception;
use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Exception\NotFoundException;
use Nqphp\Core\Http\ErrorResponseFormatter;
use Nqphp\Core\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ErrorResponseFormatterTest extends TestCase
{
    public function testFormatsJsonProblemForHttpException(): void
    {
        $formatter = new ErrorResponseFormatter(debug: false);
        $request = Request::create('/api/resource', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $exception = new NotFoundException('Item not found');
        $response = $formatter->format($request, $exception);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(404, $data['status']);
        $this->assertSame('Item not found', $data['detail']);
        $this->assertSame('/api/resource', $data['instance']);
    }

    public function testFormatsValidationExceptionWithErrors(): void
    {
        $formatter = new ErrorResponseFormatter(debug: false);
        $request = Request::create('/api/submit', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/problem+json',
        ]);

        $exception = new ValidationException(['email' => ['Email is invalid']]);
        $response = $formatter->format($request, $exception);

        $this->assertSame(422, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);

        $this->assertArrayHasKey('errors', $data);
        $this->assertSame(['email' => ['Email is invalid']], $data['errors']);
    }

    public function testHidesInternalErrorMessageInProduction(): void
    {
        $formatter = new ErrorResponseFormatter(debug: false);
        $request = Request::create('/users', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $exception = new Exception('Database connection failed with secret password');
        $response = $formatter->format($request, $exception);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal Server Error', (string) $response->getContent());
        $this->assertStringNotContainsString('secret password', (string) $response->getContent());
    }

    public function testExposesDetailsInDebugMode(): void
    {
        $formatter = new ErrorResponseFormatter(debug: true);
        $request = Request::create('/debug', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $exception = new Exception('Specific internal reason');
        $response = $formatter->format($request, $exception);

        $this->assertSame(500, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame('Specific internal reason', $data['detail']);
        $this->assertSame(Exception::class, $data['exception']);
    }

    public function testFallbackToPlainText(): void
    {
        $formatter = new ErrorResponseFormatter(debug: false);
        $request = Request::create('/plain', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/plain',
        ]);

        $exception = new HttpException(403, 'Forbidden action');
        $response = $formatter->format($request, $exception);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('text/plain', (string) $response->headers->get('Content-Type'));
        $this->assertSame('Forbidden action', $response->getContent());
    }
}
