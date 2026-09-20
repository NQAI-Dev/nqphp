<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Event;

use Nqphp\Core\Event\EventListener;
use Nqphp\Core\Event\KernelExceptionEvent;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KernelExceptionEventTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_exception_event_test_' . uniqid();
        mkdir($this->tempDir . '/src/Feature', 0777, true);
        mkdir($this->tempDir . '/src/Core', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testKernelDispatchesExceptionEventAndUsesCustomResponse(): void
    {
        $kernel = new Kernel($this->tempDir);

        $kernel->events()->listen(KernelExceptionEvent::class, function (KernelExceptionEvent $event): void {
            if ($event->getThrowable() instanceof RuntimeException) {
                $event->setResponse(new Response('Caught by Event: ' . $event->getThrowable()->getMessage(), 503));
            }
        });

        $kernel->pipe(new class implements \Nqphp\Core\Middleware\MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                throw new RuntimeException('Database unreachable');
            }
        });

        $response = $kernel->handle(Request::create('/any-endpoint'));

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('Caught by Event: Database unreachable', $response->getContent());
    }

    public function testKernelFallsBackToErrorFormatterIfEventDoesNotSetResponse(): void
    {
        $kernel = new Kernel($this->tempDir);

        $kernel->pipe(new class implements \Nqphp\Core\Middleware\MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                throw new RuntimeException('Fatal blow');
            }
        });

        $response = $kernel->handle(Request::create('/fatal'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('500 Internal Server Error', $response->getContent());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
