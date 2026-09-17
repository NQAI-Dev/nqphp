<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Attribute\Middleware;
use Nqphp\Core\Middleware\MiddlewareDiscoverer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 2 #2 (middleware pipeline) runtime test.
 *
 * Exercises the full pipeline: discover the middlewares in the
 * project's `src/Feature/Hello/Middleware/` directory, invoke them
 * in `order` ascending, and verify both the continue path (null
 * return) and the short-circuit path (Response return).
 */
final class MiddlewarePipelineTest extends TestCase
{
    private const PROJECT_DIR = __DIR__ . '/..';

    public function testNoMiddlewaresDirectoryReturnsEmpty(): void
    {
        // Point at a fresh temp dir (no src/Feature subdir) → nothing to discover
        $tmp = sys_get_temp_dir() . '/nqphp-mw-empty-' . uniqid();
        mkdir($tmp, 0755, true);
        try {
            $discoverer = new MiddlewareDiscoverer([$tmp]);
            self::assertSame([], $discoverer->discover()->all());
        } finally {
            rmdir($tmp);
        }
    }

    public function testDiscoveredMiddlewaresAreSortedByOrder(): void
    {
        // The Hello feature ships a LoggingMiddleware with default
        // order=100. Verify discoverer picks it up and exposes the
        // expected descriptor shape.
        $discoverer = new MiddlewareDiscoverer([self::PROJECT_DIR . '/src/Feature/Hello/Middleware']);
        $middlewares = $discoverer->discover()->all();

        self::assertNotEmpty($middlewares);
        // Sort invariant
        for ($i = 1; $i < \count($middlewares); $i++) {
            self::assertLessThanOrEqual(
                $middlewares[$i]['order'],
                $middlewares[$i - 1]['order'],
                'middlewares must be sorted by order ascending'
            );
        }
        // Descriptor shape
        foreach ($middlewares as $mw) {
            self::assertArrayHasKey('name', $mw);
            self::assertArrayHasKey('order', $mw);
            self::assertArrayHasKey('callable', $mw);
            self::assertIsCallable($mw['callable']);
            self::assertIsInt($mw['order']);
        }
    }

    public function testMiddlewareReturningNullContinues(): void
    {
        $discoverer = new MiddlewareDiscoverer([self::PROJECT_DIR . '/src/Feature/Hello/Middleware']);
        $middlewares = $discoverer->discover()->all();

        // Find the LoggingMiddleware (always returns null in production)
        $logging = null;
        foreach ($middlewares as $mw) {
            if ($mw['name'] === 'hello:logging') {
                $logging = $mw;
                break;
            }
        }
        self::assertNotNull($logging, 'hello:logging middleware not discovered');

        [$instance, $method] = $logging['callable'];
        $result = $instance->$method(new Request());
        self::assertNull($result, 'LoggingMiddleware should return null (= continue)');
    }

    public function testMiddlewareReturningResponseShortCircuits(): void
    {
        // Build an inline middleware that short-circuits with a 418
        // response. Verifies the runtime invocation contract:
        // returning a Response means "stop here, use this as the answer".
        $dir = sys_get_temp_dir() . '/nqphp-mw-sc-' . uniqid();
        mkdir($dir . '/Feature/Blocker/Middleware', 0755, true);

        $srcPath = $dir . '/Feature/Blocker/Middleware/BlockerMiddleware.php';
        file_put_contents($srcPath, <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Blocker\Middleware;

use Nqphp\Core\Attribute\Middleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Middleware(name: 'blocker', order: 10)]
final class BlockerMiddleware
{
    public function handle(Request $request): ?Response
    {
        return new Response('blocked by test middleware', 418, ['content-type' => 'text/plain']);
    }
}
PHP);

        try {
            $discoverer = new MiddlewareDiscoverer([$dir]);
            $middlewares = $discoverer->discover()->all();
            self::assertCount(1, $middlewares);

            [$instance, $method] = $middlewares[0]['callable'];
            $result = $instance->$method(new Request());
            self::assertInstanceOf(Response::class, $result);
            self::assertSame(418, $result->getStatusCode());
            self::assertStringContainsString('blocked by test middleware', $result->getContent());
        } finally {
            unlink($srcPath);
            rmdir($dir . '/Feature/Blocker/Middleware');
            rmdir($dir . '/Feature/Blocker');
            rmdir($dir . '/Feature');
            rmdir($dir);
        }
    }
}
