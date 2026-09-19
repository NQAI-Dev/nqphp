<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Event\KernelRequestEvent;
use Nqphp\Core\Event\KernelResponseEvent;
use Nqphp\Core\Js\JsModuleServer;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

/**
 * Plain controller fixture for route injection (no #[Controller]
 * attribute — the route is registered directly on the Router's
 * collection, mirroring KernelUrlGeneratorTest).
 */
final class KernelEventsFixtureController
{
    public function hello(): Response
    {
        return new Response('pong', 200);
    }
}

/**
 * Kernel event lifecycle integration test — kernel.request /
 * kernel.response dispatched around the real pipeline.
 */
final class KernelEventsTest extends TestCase
{
    private const PROJECT_DIR = __DIR__ . '/..';

    public function testRequestEventDispatchedAndResponseEventCarriesControllerResult(): void
    {
        $kernel = new Kernel(self::PROJECT_DIR);
        $this->addRoute($kernel, 'kernel_events:hello', '/_test/hello', KernelEventsFixtureController::class . '::hello');

        $seen = [];
        $kernel->events()->listen(
            KernelRequestEvent::class,
            function (KernelRequestEvent $e) use (&$seen) {
                $seen['request'] = $e->getRequest()->getPathInfo();
            }
        );
        $kernel->events()->listen(
            KernelResponseEvent::class,
            function (KernelResponseEvent $e) use (&$seen) {
                $seen['response'] = $e->getResponse()->getStatusCode();
                $seen['content'] = (string) $e->getResponse()->getContent();
            }
        );

        $response = $kernel->handle(Request::create('/_test/hello', 'GET'));

        self::assertSame('/_test/hello', $seen['request'] ?? null);
        self::assertSame(200, $seen['response'] ?? null);
        self::assertSame('pong', $seen['content'] ?? null);
        self::assertSame(200, $response->getStatusCode());
    }

    public function testRequestListenerShortCircuitsPipeline(): void
    {
        $kernel = new Kernel(self::PROJECT_DIR);

        $kernel->events()->listen(
            KernelRequestEvent::class,
            function (KernelRequestEvent $e) {
                $e->setResponse(new Response('blocked by listener', 418, ['X-Listener' => 'yes']));
            }
        );

        // Unrouted path: if the pipeline ran, we would get 404, not 418.
        $response = $kernel->handle(Request::create('/definitely/not/routed', 'GET'));

        self::assertSame(418, $response->getStatusCode());
        self::assertSame('blocked by listener', $response->getContent());
        self::assertSame('yes', $response->headers->get('X-Listener'));
    }

    public function testResponseListenerCanMutateAndReplaceResponse(): void
    {
        $kernel = new Kernel(self::PROJECT_DIR);

        $kernel->events()->listen(
            KernelResponseEvent::class,
            function (KernelResponseEvent $e) {
                $e->getResponse()->headers->set('X-Nqphp', '1');
                if ($e->getResponse()->getStatusCode() === 404) {
                    $e->setResponse(new Response('replaced', 200));
                }
            }
        );

        $this->addRoute($kernel, 'kernel_events:mutate', '/_test/mutate', KernelEventsFixtureController::class . '::hello');
        $r1 = $kernel->handle(Request::create('/_test/mutate', 'GET'));
        self::assertSame('1', $r1->headers->get('X-Nqphp'));

        $r2 = $kernel->handle(Request::create('/no/such/route', 'GET'));
        self::assertSame(200, $r2->getStatusCode());
        self::assertSame('replaced', $r2->getContent());
    }

    public function testResponseEventNotFiredForInternalNamespace(): void
    {
        $tmp = sys_get_temp_dir() . '/nqphp-kernel-events-js-' . uniqid();
        mkdir($tmp . '/fw', 0755, true);
        mkdir($tmp . '/feat', 0755, true);

        $kernel = new Kernel(
            self::PROJECT_DIR,
            null,
            new JsModuleServer(frameworkRoots: [$tmp . '/fw'], featureRoots: [$tmp . '/feat']),
        );

        $fired = false;
        $kernel->events()->listen(
            KernelResponseEvent::class,
            function () use (&$fired) {
                $fired = true;
            }
        );

        $response = $kernel->handle(Request::create('/_nqphp/js/nothing.js', 'GET'));

        self::assertFalse($fired);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testKernelExposesEventListenerDiscoverer(): void
    {
        $kernel = new Kernel(self::PROJECT_DIR);
        self::assertInstanceOf(
            \Nqphp\Core\Event\EventListenerDiscoverer::class,
            $kernel->eventListenerDiscoverer()
        );
        // Discovery over the real tree runs without errors and returns
        // descriptors with the expected shape.
        foreach ($kernel->eventListenerDiscoverer()->discover() as $listener) {
            self::assertArrayHasKey('event', $listener);
            self::assertArrayHasKey('callable', $listener);
            self::assertArrayHasKey('priority', $listener);
            self::assertArrayHasKey('method', $listener);
        }
    }

    /**
     * Inject a named route directly into the Kernel's live Router
     * collection (same reflection pattern as KernelUrlGeneratorTest).
     */
    private function addRoute(Kernel $kernel, string $name, string $path, string $controller): void
    {
        $ref = new \ReflectionClass($kernel->router);
        $prop = $ref->getProperty('routes');
        $prop->getValue($kernel->router)->add($name, new Route($path, ['_controller' => $controller]));
    }
}
