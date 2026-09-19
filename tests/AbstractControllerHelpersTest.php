<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AbstractControllerHelpersTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel(__DIR__ . '/fixtures/redirectapp');
    }

    public function testRedirectToRouteDefaultStatus302(): void
    {
        $request = Request::create('/go-home', 'GET');
        $response = $this->kernel->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/home', $response->getTargetUrl());
    }

    public function testRedirectToRoutePermanent301(): void
    {
        $request = Request::create('/go-home-permanent', 'GET');
        $response = $this->kernel->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(301, $response->getStatusCode());
        $this->assertStringContainsString('/home', $response->getTargetUrl());
    }

    public function testRedirectToRouteWithParams(): void
    {
        $request = Request::create('/go-blog', 'GET');
        $response = $this->kernel->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/blog/hello-world', $response->getTargetUrl());
    }

    public function testNormalRouteStillWorks(): void
    {
        $request = Request::create('/home', 'GET');
        $response = $this->kernel->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('home page', $response->getContent());
    }
}
