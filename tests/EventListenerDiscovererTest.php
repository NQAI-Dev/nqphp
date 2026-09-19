<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Event\EventDispatcher;
use Nqphp\Core\Event\EventListenerDiscoverer;
use Nqphp\Tests\Core\Event\DispatcherProbeEvent;
use PHPUnit\Framework\TestCase;

/**
 * EventListenerDiscoverer test — #[EventListener] method discovery,
 * priority sorting, event-class resolution and attach().
 */
final class EventListenerDiscovererTest extends TestCase
{
    public function testDiscoversNothingWhenNoDirs(): void
    {
        $discoverer = new EventListenerDiscoverer(['/nonexistent/path']);
        self::assertSame([], $discoverer->discover());
    }

    public function testDiscoversListenerWithEventClassAndPriority(): void
    {
        $dir = sys_get_temp_dir() . '/nqphp-listener-test-' . uniqid();
        mkdir($dir . '/Feature/Foo/Listener', 0755, true);
        file_put_contents($dir . '/Feature/Foo/Listener/MyListener.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Foo\Listener;

use Nqphp\Core\Attribute\EventListener;
use Nqphp\Tests\Core\Event\DispatcherProbeEvent;

final class MyListener
{
    #[EventListener(priority: 10)]
    public static function high(DispatcherProbeEvent $event): void
    {
        $event->notes[] = 'high';
    }

    #[EventListener]
    public static function low(DispatcherProbeEvent $event): void
    {
        $event->notes[] = 'low';
    }
}
PHP);

        $discoverer = new EventListenerDiscoverer([$dir . '/Feature']);
        $listeners = $discoverer->discover();

        // Fixture + the framework's own src/Core + src/Feature are not
        // scanned here — only the temp dir.
        self::assertCount(2, $listeners);
        self::assertSame(DispatcherProbeEvent::class, $listeners[0]['event']);
        self::assertSame(10, $listeners[0]['priority']);
        self::assertSame('high', substr($listeners[0]['method'], -4));
        self::assertSame(0, $listeners[1]['priority']);

        $probe = new DispatcherProbeEvent();
        $dispatcher = new EventDispatcher();
        $discoverer->attach($dispatcher);
        $dispatcher->dispatch($probe);

        self::assertSame(['high', 'low'], $probe->notes);
    }

    public function testSkipsMethodsWithoutSingleTypedParam(): void
    {
        $dir = sys_get_temp_dir() . '/nqphp-listener-skip-' . uniqid();
        mkdir($dir . '/Bar', 0755, true);
        file_put_contents($dir . '/Bar/BadListener.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Bar;

use Nqphp\Core\Attribute\EventListener;
use Nqphp\Tests\Core\Event\DispatcherProbeEvent;

final class BadListener
{
    #[EventListener]
    public function noParams(): void {}

    #[EventListener]
    public function optionalParam(?DispatcherProbeEvent $e = null): void {}

    #[EventListener]
    public function twoParams(DispatcherProbeEvent $a, DispatcherProbeEvent $b): void {}
}
PHP);

        $discoverer = new EventListenerDiscoverer([$dir]);

        self::assertSame([], $discoverer->discover());
    }

    public function testInstantiatesNonStaticListenerClass(): void
    {
        $dir = sys_get_temp_dir() . '/nqphp-listener-inst-' . uniqid();
        mkdir($dir . '/Baz', 0755, true);
        file_put_contents($dir . '/Baz/InstanceListener.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Baz;

use Nqphp\Core\Attribute\EventListener;
use Nqphp\Tests\Core\Event\DispatcherProbeEvent;

final class InstanceListener
{
    #[EventListener]
    public function onProbe(DispatcherProbeEvent $event): void
    {
        $event->notes[] = 'instance';
    }
}
PHP);

        $dispatcher = new EventDispatcher();
        (new EventListenerDiscoverer([$dir]))->attach($dispatcher);

        $probe = new DispatcherProbeEvent();
        $dispatcher->dispatch($probe);

        self::assertSame(['instance'], $probe->notes);
    }
}
