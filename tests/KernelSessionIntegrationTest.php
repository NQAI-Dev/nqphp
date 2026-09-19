<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Controller\AbstractController;
use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Session\SessionInterface;
use Nqphp\Core\Session\SessionManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

final class SessionControllerFixture extends AbstractController
{
    public function show(SessionInterface $session): Response
    {
        return new Response('injected:' . ($session instanceof SessionManager ? 'ok' : 'fail'));
    }

    public function helper(): Response
    {
        $this->session()->set('fixture_key', 'fixture_val');
        return new Response('val:' . $this->session()->get('fixture_key'));
    }

    public function flashProducer(): Response
    {
        $this->addFlash('success', 'Operation completed');
        return new Response('flash_set');
    }

    public function flashConsumer(): Response
    {
        $messages = $this->getFlash('success');
        return new Response('flashes:' . implode(',', $messages));
    }
}

final class KernelSessionIntegrationTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testKernelExposesSessionInstanceAndInterface(): void
    {
        $kernel = new Kernel(__DIR__ . '/..');
        $session = $kernel->session();

        $this->assertInstanceOf(SessionInterface::class, $session);
        $this->assertInstanceOf(SessionManager::class, $session);
        $this->assertFalse($session->isStarted());

        $session->set('test_var', 'hello_session');
        $this->assertTrue($session->isStarted());
        $this->assertTrue($session->has('test_var'));
        $this->assertSame('hello_session', $session->get('test_var'));
        $this->assertFalse($session->has('non_existent'));

        $session->remove('test_var');
        $this->assertFalse($session->has('test_var'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testKernelAutowiresSessionInterfaceIntoControllerAction(): void
    {
        $kernel = new Kernel(__DIR__ . '/..');
        $this->addRoute(
            $kernel,
            '/_test/session/inject',
            SessionControllerFixture::class . '::show'
        );

        $response = $kernel->handle(Request::create('/_test/session/inject'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('injected:ok', $response->getContent());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAbstractControllerSessionHelperWorksDuringDispatch(): void
    {
        $kernel = new Kernel(__DIR__ . '/..');
        $this->addRoute(
            $kernel,
            '/_test/session/helper',
            SessionControllerFixture::class . '::helper'
        );

        $response = $kernel->handle(Request::create('/_test/session/helper'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('val:fixture_val', $response->getContent());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAbstractControllerFlashHelpers(): void
    {
        $kernel = new Kernel(__DIR__ . '/..');
        $this->addRoute(
            $kernel,
            '/_test/session/flash-produce',
            SessionControllerFixture::class . '::flashProducer'
        );
        $this->addRoute(
            $kernel,
            '/_test/session/flash-consume',
            SessionControllerFixture::class . '::flashConsumer'
        );

        $res1 = $kernel->handle(Request::create('/_test/session/flash-produce'));
        $this->assertSame('flash_set', $res1->getContent());

        $res2 = $kernel->handle(Request::create('/_test/session/flash-consume'));
        $this->assertSame('flashes:Operation completed', $res2->getContent());

        // Consuming again should yield empty flashes
        $res3 = $kernel->handle(Request::create('/_test/session/flash-consume'));
        $this->assertSame('flashes:', $res3->getContent());
    }

    private function addRoute(Kernel $kernel, string $path, string $controller): void
    {
        $reflection = new \ReflectionClass($kernel->router);
        $routes = $reflection->getProperty('routes')->getValue($kernel->router);
        $routes->add('session_test:' . md5($path), new Route($path, [
            '_controller' => $controller,
        ]));
    }
}
