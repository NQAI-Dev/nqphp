<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Service\ServiceDiscoverer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

interface GreetingService
{
    public function greet(string $name): string;
}

interface AmbiguousService
{
    public function value(): string;
}

final class ControllerArgumentFixture
{
    public function injected(string $name, GreetingService $greeter, Request $request, Kernel $kernel): string
    {
        return $greeter->greet($name) . '|' . $request->getMethod() . '|' . ($kernel instanceof Kernel ? 'kernel' : 'none');
    }

    public function routeValueWins(GreetingService $greeter): string
    {
        return $greeter->greet('route');
    }

    public function optional(string $suffix = 'default'): string
    {
        return $suffix;
    }

    public function ambiguous(AmbiguousService $service): string
    {
        return $service->value();
    }
}

final class ControllerArgumentInjectionTest extends TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            foreach (glob($dir . '/*.php') ?: [] as $file) {
                unlink($file);
            }
            rmdir($dir);
        }
    }

    public function testInjectsRequestKernelAndUniqueTypedServiceAlongsideRouteValues(): void
    {
        $kernel = $this->kernelWithServices([
            'Greeter.php' => <<<'PHP'
<?php
namespace Nqphp\Tests\GeneratedInjection;
use Nqphp\Core\Attribute\Service;
use Nqphp\Tests\GreetingService;
#[Service(name: 'test.greeter')]
final class Greeter implements GreetingService
{
    public function greet(string $name): string { return "Hello, $name"; }
}
PHP,
        ]);
        $this->addRoute(
            $kernel,
            '/_test/inject/{name}',
            ControllerArgumentFixture::class . '::injected',
        );

        $response = $kernel->handle(Request::create('/_test/inject/Ada', 'POST'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Hello, Ada|POST|kernel', $response->getContent());
        self::assertSame($kernel->service('test.greeter'), $kernel->service('test.greeter'));
    }

    public function testExplicitRouteValueTakesPrecedenceOverTypeInjection(): void
    {
        $kernel = $this->kernelWithServices([]);
        $this->addRoute(
            $kernel,
            '/_test/route-value',
            ControllerArgumentFixture::class . '::routeValueWins',
            ['greeter' => new class () implements GreetingService {
                public function greet(string $name): string
                {
                    return 'From ' . $name;
                }
            }],
        );

        $response = $kernel->handle(Request::create('/_test/route-value'));

        self::assertSame('From route', $response->getContent());
    }

    public function testUsesDefaultValueWhenNoInjectionCandidateExists(): void
    {
        $kernel = $this->kernelWithServices([]);
        $this->addRoute($kernel, '/_test/optional', ControllerArgumentFixture::class . '::optional');

        self::assertSame('default', $kernel->handle(Request::create('/_test/optional'))->getContent());
    }

    public function testRejectsAmbiguousTypedServiceInjection(): void
    {
        $kernel = $this->kernelWithServices([
            'First.php' => <<<'PHP'
<?php
namespace Nqphp\Tests\GeneratedInjection;
use Nqphp\Core\Attribute\Service;
use Nqphp\Tests\AmbiguousService;
#[Service(name: 'test.first')]
final class First implements AmbiguousService { public function value(): string { return 'first'; } }
PHP,
            'Second.php' => <<<'PHP'
<?php
namespace Nqphp\Tests\GeneratedInjection;
use Nqphp\Core\Attribute\Service;
use Nqphp\Tests\AmbiguousService;
#[Service(name: 'test.second')]
final class Second implements AmbiguousService { public function value(): string { return 'second'; } }
PHP,
        ]);
        $this->addRoute($kernel, '/_test/ambiguous', ControllerArgumentFixture::class . '::ambiguous');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('matches multiple services (test.first, test.second)');
        $kernel->handle(Request::create('/_test/ambiguous'));
    }

    /**
     * @param array<string,string> $files
     */
    private function kernelWithServices(array $files): Kernel
    {
        $dir = sys_get_temp_dir() . '/nqphp-controller-di-' . uniqid('', true);
        mkdir($dir, 0755, true);
        $this->tempDirs[] = $dir;
        foreach ($files as $name => $source) {
            file_put_contents($dir . '/' . $name, $source);
        }

        $kernel = new Kernel(__DIR__ . '/..');
        $kernel->serviceDiscoverer = new ServiceDiscoverer([$dir]);
        return $kernel;
    }

    /**
     * @param array<string,mixed> $defaults
     */
    private function addRoute(Kernel $kernel, string $path, string $controller, array $defaults = []): void
    {
        $defaults['_controller'] = $controller;
        $reflection = new \ReflectionClass($kernel->router);
        $routes = $reflection->getProperty('routes')->getValue($kernel->router);
        $routes->add('controller_di:' . md5($path), new Route($path, $defaults));
    }
}
