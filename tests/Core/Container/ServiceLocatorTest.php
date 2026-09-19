<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Container;

use LogicException;
use Nqphp\Core\Container\ServiceLocator;
use PHPUnit\Framework\TestCase;

// ─── Fixtures ────────────────────────────────────────────────────────────────

final class NoDepsService
{
    public string $id;
    public function __construct() { $this->id = uniqid('svc_', true); }
}

interface GreeterInterface
{
    public function greet(): string;
}

final class HelloGreeter implements GreeterInterface
{
    public function greet(): string { return 'hello'; }
}

final class WithDepService
{
    public function __construct(
        public readonly NoDepsService $dep,
    ) {}
}

final class DeepDepService
{
    public function __construct(
        public readonly WithDepService $mid,
    ) {}
}

final class OptionalDepService
{
    public function __construct(
        public readonly ?NoDepsService $dep = null,
    ) {}
}

// Circular A → B → A
final class CircularA
{
    public function __construct(CircularB $b) {}
}
final class CircularB
{
    public function __construct(CircularA $a) {}
}

// ─── Tests ───────────────────────────────────────────────────────────────────

final class ServiceLocatorTest extends TestCase
{
    public function testMakeNoDeps(): void
    {
        $loc = new ServiceLocator();
        $svc = $loc->make(NoDepsService::class);

        $this->assertInstanceOf(NoDepsService::class, $svc);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $loc = new ServiceLocator();
        $a = $loc->make(NoDepsService::class);
        $b = $loc->make(NoDepsService::class);

        $this->assertSame($a, $b);
    }

    public function testPrototypeScopeReturnsDifferentInstances(): void
    {
        $loc = new ServiceLocator();
        $loc->prototype(NoDepsService::class);

        $a = $loc->make(NoDepsService::class);
        $b = $loc->make(NoDepsService::class);

        $this->assertNotSame($a, $b);
    }

    public function testBindInterfaceToConcrete(): void
    {
        $loc = new ServiceLocator();
        $loc->bind(GreeterInterface::class, HelloGreeter::class);

        $greeter = $loc->make(GreeterInterface::class);

        $this->assertInstanceOf(HelloGreeter::class, $greeter);
        $this->assertSame('hello', $greeter->greet());
    }

    public function testBindFactory(): void
    {
        $loc = new ServiceLocator();
        $loc->bindFactory('clock', fn () => new \stdClass());

        $obj = $loc->make('clock');
        $this->assertInstanceOf(\stdClass::class, $obj);

        // singleton by default — same instance on second call
        $obj2 = $loc->make('clock');
        $this->assertSame($obj, $obj2);
    }

    public function testBindFactoryPrototype(): void
    {
        $loc = new ServiceLocator();
        $loc->bindFactory('counter', fn () => new \stdClass());
        $loc->prototype('counter');

        $this->assertNotSame($loc->make('counter'), $loc->make('counter'));
    }

    public function testInstanceRegistration(): void
    {
        $loc = new ServiceLocator();
        $prebuilt = new NoDepsService();
        $loc->instance(NoDepsService::class, $prebuilt);

        $this->assertSame($prebuilt, $loc->make(NoDepsService::class));
    }

    public function testAutowiresConstructorDependency(): void
    {
        $loc = new ServiceLocator();
        $svc = $loc->make(WithDepService::class);

        $this->assertInstanceOf(WithDepService::class, $svc);
        $this->assertInstanceOf(NoDepsService::class, $svc->dep);
    }

    public function testDeepAutowiring(): void
    {
        $loc = new ServiceLocator();
        $svc = $loc->make(DeepDepService::class);

        $this->assertInstanceOf(DeepDepService::class, $svc);
        $this->assertInstanceOf(WithDepService::class, $svc->mid);
        $this->assertInstanceOf(NoDepsService::class, $svc->mid->dep);
    }

    public function testOptionalNullableDep(): void
    {
        $loc = new ServiceLocator();
        $svc = $loc->make(OptionalDepService::class);

        // NoDepsService is resolvable, so it should be autowired
        $this->assertInstanceOf(NoDepsService::class, $svc->dep);
    }

    public function testHasReturnsTrueForKnownClass(): void
    {
        $loc = new ServiceLocator();
        $this->assertTrue($loc->has(NoDepsService::class));
        $this->assertFalse($loc->has('App\\NonExistent\\Class'));
    }

    public function testHasReturnsTrueForBoundAlias(): void
    {
        $loc = new ServiceLocator();
        $loc->bind(GreeterInterface::class, HelloGreeter::class);
        $this->assertTrue($loc->has(GreeterInterface::class));
    }

    public function testCircularDependencyThrows(): void
    {
        $loc = new ServiceLocator();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/[Cc]ircular/');

        $loc->make(CircularA::class);
    }

    public function testUnresolvableClassThrows(): void
    {
        $loc = new ServiceLocator();

        $this->expectException(LogicException::class);
        $loc->make('App\\DoesNotExist');
    }

    public function testSingletonCallExplicitlyPreservesScope(): void
    {
        $loc = new ServiceLocator();
        $loc->prototype(NoDepsService::class);
        $loc->singleton(NoDepsService::class); // override back

        $a = $loc->make(NoDepsService::class);
        $b = $loc->make(NoDepsService::class);

        $this->assertSame($a, $b);
    }
}
