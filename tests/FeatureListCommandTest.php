<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Phase 2 #1 (per-feature config) test + DX check for `feature:list`.
 *
 * Verifies the FeatureListCommand runs end-to-end against a real
 * Kernel — config / routes / middlewares are pulled from the live
 * discoverer / config loader (no mocks).
 */
final class FeatureListCommandTest extends TestCase
{
    private const PROJECT_DIR = __DIR__ . '/..';

    public function testCommandListsHelloFeatureWithConfig(): void
    {
        $kernel = new Kernel(self::PROJECT_DIR);
        $app = new Application('nqphp');
        $cmd = $app->find('feature:list');
        // Manually inject the kernel — Application::find picks the
        // command by name, but the constructor needs the kernel.
        $ref = new \ReflectionClass($cmd);
        $ctor = $ref->getConstructor();
        $cmd = $ctor->getNumberOfRequiredParameters() === 1
            ? $ref->newInstance($kernel)
            : $cmd;
        $app->add($cmd);

        $tester = new CommandTester($app->find('feature:list'));
        $tester->execute([]);
        $output = $tester->getDisplay();

        self::assertStringContainsString('Discovered features', $output);
        self::assertStringContainsString('Hello', $output);
        self::assertStringContainsString('cache_ttl', $output);
        self::assertStringContainsString('60', $output);
        // Routes from HelloController are named like "Hello:index" — the
        // command prints them in the per-feature routes block.
        self::assertStringContainsString('Hello:index', $output);
    }
}
