<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Controller;

use Nqphp\Core\Controller\AbstractController;
use Nqphp\Core\Http\RedirectResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DummyController extends AbstractController
{
    public function testRenderString(string $body, int $status = 200, array $headers = []): Response
    {
        return $this->renderString($body, $status, $headers);
    }

    public function testJson(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return $this->json($data, $status, $headers);
    }

    public function testRedirect(string $url, int $status = 302): RedirectResponse
    {
        return $this->redirect($url, $status);
    }

    public function testSession(): void
    {
        $this->session();
    }

    public function testValidate(): void
    {
        $this->validate(\stdClass::class);
    }

    public function testRedirectToRoute(): void
    {
        $this->redirectToRoute('home');
    }

    public function testGetUser(): void
    {
        $this->getUser();
    }
}

class AbstractControllerTest extends TestCase
{
    public function testRenderStringReturnsResponse(): void
    {
        $controller = new DummyController();
        $res = $controller->testRenderString('<h1>Hello</h1>', 201, ['X-Custom' => 'val']);

        $this->assertSame(201, $res->getStatusCode());
        $this->assertSame('<h1>Hello</h1>', $res->getContent());
        $this->assertSame('val', $res->headers->get('X-Custom'));
    }

    public function testJsonReturnsJsonResponse(): void
    {
        $controller = new DummyController();
        $res = $controller->testJson(['status' => 'ok'], 200);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('{"status":"ok"}', $res->getContent());
    }

    public function testRedirectWithoutKernel(): void
    {
        $controller = new DummyController();
        $res = $controller->testRedirect('/login', 302);

        $this->assertSame(302, $res->getStatusCode());
        $this->assertSame('/login', $res->getTargetUrl());
    }

    public function testKernelDependentMethodsThrowWithoutKernel(): void
    {
        $controller = new DummyController();

        $this->expectException(RuntimeException::class);
        $controller->testSession();
    }

    public function testValidateThrowsWithoutKernel(): void
    {
        $controller = new DummyController();

        $this->expectException(RuntimeException::class);
        $controller->testValidate();
    }

    public function testRedirectToRouteThrowsWithoutKernel(): void
    {
        $controller = new DummyController();

        $this->expectException(RuntimeException::class);
        $controller->testRedirectToRoute();
    }

    public function testGetUserThrowsWithoutKernel(): void
    {
        $controller = new DummyController();

        $this->expectException(RuntimeException::class);
        $controller->testGetUser();
    }
}
