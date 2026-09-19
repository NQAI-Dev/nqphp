<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Exception\NotFoundException;
use Nqphp\Core\Http\ErrorResponseFormatter;
use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Middleware\ErrorHandlerMiddleware;
use Nqphp\Core\Middleware\MiddlewareInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

/**
 * Onion pipeline test: Kernel::pipe() middlewares wrap the whole
 * request lifecycle (events → CSRF → discovered middleware → hooks →
 * controller), plus the top-level error boundary (catch semantics).
 */
final class KernelPipelineFixtureController
{
    public function ok(): Response
    {
        return new Response('core', 200);
    }

    public function boom(): Response
    {
        throw new \RuntimeException('controller exploded');
    }

    public function missing(): Response
    {
        throw new NotFoundException('No such thing');
    }
}

/** Records call order into a shared trace and decorates the outgoing response. */
final class TracingPipeMiddleware implements MiddlewareInterface
{
    /** @param list<string> $trace */
    public function __construct(
        private readonly string $label,
        /** @var list<string> */
        private array &$trace,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $this->trace[] = $this->label . ':in';
        $response = $next($request);
        $this->trace[] = $this->label . ':out';
        $response->headers->set('X-Pipe-Order', ($response->headers->get('X-Pipe-Order') !== null
            ? $response->headers->get('X-Pipe-Order') . ','
            : '') . $this->label);

        return $response;
    }
}

final class KernelPipelineTest extends TestCase
{
    private const PROJECT_DIR = __DIR__ . '/..';

    private Kernel $kernel;

    /** @var list<string> */
    private array $trace = [];

    protected function setUp(): void
    {
        $this->kernel = new Kernel(self::PROJECT_DIR);
        $this->trace = [];
    }

    public function testPipesWrapCoreLifecycleInRegistrationOrder(): void
    {
        $this->kernel->pipe(new TracingPipeMiddleware('outer', $this->trace));
        $this->kernel->pipe(new TracingPipeMiddleware('inner', $this->trace));
        $this->addRoute($this->kernel, 'pipeline:ok', '/_test/pipeline-ok', KernelPipelineFixtureController::class . '::ok');

        $response = $this->kernel->handle(Request::create('/_test/pipeline-ok'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('core', $response->getContent());
        // First registered = outermost: in outer → in inner → core → out inner → out outer.
        self::assertSame(
            ['outer:in', 'inner:in', 'inner:out', 'outer:out'],
            $this->trace,
        );
        // Outgoing decoration accumulates from innermost outward.
        self::assertSame('inner,outer', $response->headers->get('X-Pipe-Order'));
    }

    public function testPipesSeeKernelRequestEventInsidePipeline(): void
    {
        $seen = [];
        $this->kernel->events()->listen(
            \Nqphp\Core\Event\KernelRequestEvent::class,
            function () use (&$seen): void {
                $seen[] = 'event';
            },
        );

        $pipe = new class implements MiddlewareInterface {
            public array $seen = [];

            public function process(Request $request, callable $next): Response
            {
                $this->seen[] = 'pipe:in';
                return $next($request);
            }
        };
        $this->kernel->pipe($pipe);
        $this->addRoute($this->kernel, 'pipeline:event', '/_test/pipeline-event', KernelPipelineFixtureController::class . '::ok');

        $this->kernel->handle(Request::create('/_test/pipeline-event'));

        // kernel.request fires inside the onion — the pipe observed it.
        self::assertSame(['pipe:in', 'event'], array_merge($pipe->seen, $seen));
    }

    public function testPipeCanShortCircuitBeforeController(): void
    {
        $this->kernel->pipe(new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                if ($request->getPathInfo() === '/_test/deny') {
                    return new Response('denied by pipe', 403);
                }

                return $next($request);
            }
        });
        $this->addRoute($this->kernel, 'pipeline:deny', '/_test/deny', KernelPipelineFixtureController::class . '::ok');

        $response = $this->kernel->handle(Request::create('/_test/deny'));

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('denied by pipe', $response->getContent());
    }

    public function testPipesAccessorExposesRegisteredMiddlewares(): void
    {
        $first = new TracingPipeMiddleware('a', $this->trace);
        $second = new TracingPipeMiddleware('b', $this->trace);
        $this->kernel->pipe($first)->pipe($second);

        self::assertSame([$first, $second], $this->kernel->pipes());
    }

    public function testControllerThrowableConvertedToSafeResponseWhenCatchIsTrue(): void
    {
        $this->addRoute($this->kernel, 'pipeline:boom', '/_test/boom', KernelPipelineFixtureController::class . '::boom');

        $response = $this->kernel->handle(
            Request::create('/_test/boom', 'GET', server: ['HTTP_ACCEPT' => 'text/plain'])
        );

        self::assertSame(500, $response->getStatusCode());
        // Non-HttpException messages never leak in production mode.
        self::assertSame('Internal Server Error', $response->getContent());
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function testControllerHttpExceptionMapsToItsStatusCode(): void
    {
        $this->addRoute($this->kernel, 'pipeline:missing', '/_test/missing', KernelPipelineFixtureController::class . '::missing');

        $response = $this->kernel->handle(
            Request::create('/_test/missing', 'GET', server: ['HTTP_ACCEPT' => 'application/json'])
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame(404, $body['status']);
        self::assertSame('No such thing', $body['detail']);
        self::assertSame('/_test/missing', $body['instance']);
    }

    public function testCatchFalseRethrowsControllerThrowable(): void
    {
        $this->addRoute($this->kernel, 'pipeline:boom2', '/_test/boom2', KernelPipelineFixtureController::class . '::boom');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('controller exploded');
        $this->kernel->handle(
            Request::create('/_test/boom2'),
            \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
            false,
        );
    }

    public function testDebugFormatterLeaksExceptionClassAndMessage(): void
    {
        $kernel = new Kernel(self::PROJECT_DIR, errorFormatter: new ErrorResponseFormatter(debug: true));
        $this->addRoute($kernel, 'pipeline:debug', '/_test/debug-boom', KernelPipelineFixtureController::class . '::boom');

        $response = $kernel->handle(
            Request::create('/_test/debug-boom', 'GET', server: ['HTTP_ACCEPT' => 'application/json'])
        );

        self::assertSame(500, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame(\RuntimeException::class, $body['exception'] ?? null);
        // debug mode intentionally surfaces the exception message.
        self::assertSame('controller exploded', $body['detail']);
    }

    public function testErrorHandlerMiddlewareViaPipeFormatsThrowables(): void
    {
        $this->kernel->pipe(new ErrorHandlerMiddleware(new ErrorResponseFormatter()));
        $this->addRoute($this->kernel, 'pipeline:handler', '/_test/handler-boom', KernelPipelineFixtureController::class . '::boom');

        $response = $this->kernel->handle(
            Request::create('/_test/handler-boom', 'GET', server: ['HTTP_ACCEPT' => 'text/plain'])
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('Internal Server Error', $response->getContent());
    }

    public function testThrowInsidePipeItselfIsCaughtByErrorBoundary(): void
    {
        $this->kernel->pipe(new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                throw new \LogicException('pipe is broken');
            }
        });

        $response = $this->kernel->handle(
            Request::create('/anything', 'GET', server: ['HTTP_ACCEPT' => 'text/plain'])
        );

        self::assertSame(500, $response->getStatusCode());
    }

    /**
     * @param array<string,mixed> $defaults
     */
    private function addRoute(Kernel $kernel, string $name, string $path, string $controller, array $defaults = []): void
    {
        $defaults['_controller'] = $controller;
        $reflection = new \ReflectionClass($kernel->router);
        $routes = $reflection->getProperty('routes')->getValue($kernel->router);
        $routes->add($name, new Route($path, $defaults));
    }
}
