<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * End-to-end tests for the Hello feature.
 *
 * These exercise the full Phase 1 pipeline:
 *   request → Kernel::handle → Router::discover → match → dispatch → controller → response
 *
 * Where SmokeTest only confirms classes load (cheap), these verify
 * the wiring actually delivers the right response for each route.
 *
 * PHPUnit's standard Response methods (getStatusCode, getContent,
 * getHeaders) are used to keep the assertions framework-specific —
 * any HTTP-client approach that produces Symfony Responses works.
 */
final class HelloFeatureTest extends TestCase
{
    private function kernel(): Kernel
    {
        return new Kernel(dirname(__DIR__));
    }

    public function testIndexReturnsHelloWorldHtml(): void
    {
        $kernel = $this->kernel();
        $request = Request::create('/', 'GET');
        $response = $kernel->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Hello World', $response->getContent());
        self::assertStringContainsString('nqphp', $response->getContent());
        // Default Symfony Response uses text/html; the framework doesn't
        // override Content-Type for HTML responses in Phase 1.
        self::assertSame('text/html; charset=utf-8', $response->headers->get('Content-Type'));
    }

    public function testJsonReturnsHelloObject(): void
    {
        $kernel = $this->kernel();
        $request = Request::create('/json', 'GET');
        $response = $kernel->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $decoded = json_decode((string) $response->getContent(), true);
        self::assertSame(['hello' => 'world'], $decoded);
    }

    public function testJsonWithNameSubstitutesPathParam(): void
    {
        $kernel = $this->kernel();
        $request = Request::create('/json/Edu', 'GET');
        $response = $kernel->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $decoded = json_decode((string) $response->getContent(), true);
        self::assertSame(['hello' => 'Edu'], $decoded);
    }

    public function testUnknownRouteReturns404(): void
    {
        $kernel = $this->kernel();
        $request = Request::create('/no-such-thing', 'GET');
        $response = $kernel->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }
}
