<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Service\ServiceListCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ServiceListCommandTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/nqphp-service-command-' . bin2hex(random_bytes(6));
        mkdir($this->projectDir . '/src/Feature/Demo/Service', 0777, true);
        file_put_contents(
            $this->projectDir . '/src/Feature/Demo/Service/Clock.php',
            <<<'PHP'
<?php
namespace Nqphp\CommandFixture;

use Nqphp\Core\Attribute\Service;

#[Service(name: 'demo.clock', scope: 'prototype')]
final class Clock {}
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    // ── basic listing ──────────────────────────────────────────────────────

    public function testCommandListsDiscoveredServices(): void
    {
        $output = $this->runCommand()->getDisplay();

        self::assertStringContainsString('Discovered services', $output);
        self::assertStringContainsString('NAME', $output);
        self::assertStringContainsString('SCOPE', $output);
        self::assertStringContainsString('CLASS', $output);
        self::assertStringContainsString('TAGS', $output);
        self::assertStringContainsString('demo.clock', $output);
        self::assertStringContainsString('prototype', $output);
        self::assertStringContainsString('Nqphp\\CommandFixture\\Clock', $output);
    }

    public function testCommandReturnsSuccess(): void
    {
        self::assertSame(Command::SUCCESS, $this->runCommand()->getStatusCode());
    }

    // ── tags ───────────────────────────────────────────────────────────────

    public function testTagsShownInOutput(): void
    {
        $this->writeService('Demo', 'Mailer', 'demo.mailer', 'singleton', ['notifier', 'async']);

        $output = $this->runCommand()->getDisplay();
        self::assertStringContainsString('notifier', $output);
        self::assertStringContainsString('async', $output);
    }

    public function testFilterByTagShowsOnlyMatchingServices(): void
    {
        $this->writeService('Demo', 'Mailer', 'demo.mailer', 'singleton', ['notifier']);
        $this->writeService('Demo', 'Logger', 'demo.logger', 'singleton', ['monitoring']);

        $output = $this->runCommand(['--tag' => 'notifier'])->getDisplay();

        self::assertStringContainsString('demo.mailer', $output);
        self::assertStringNotContainsString('demo.logger', $output);
    }

    public function testFilterByTagWithNoMatchShowsNote(): void
    {
        $output = $this->runCommand(['--tag' => 'nonexistent'])->getDisplay();

        self::assertStringContainsString("No services with tag 'nonexistent'", $output);
        self::assertSame(Command::SUCCESS, $this->runCommand(['--tag' => 'nonexistent'])->getStatusCode());
    }

    public function testEmptyTagsColumnWhenNoTags(): void
    {
        // Clock fixture has no tags — TAGS column should exist but cell empty
        $output = $this->runCommand()->getDisplay();
        self::assertStringContainsString('TAGS', $output);
    }

    // ── empty project ─────────────────────────────────────────────────────

    public function testNoServicesNoteWhenProjectEmpty(): void
    {
        $emptyDir = sys_get_temp_dir() . '/nqphp-svc-empty-' . bin2hex(random_bytes(6));
        mkdir($emptyDir . '/src/Feature', 0777, true);

        try {
            $app = new Application('nqphp');
            $app->add(new ServiceListCommand(new Kernel($emptyDir)));
            $tester = new CommandTester($app->find('service:list'));
            $tester->execute([]);

            self::assertStringContainsString('No services discovered', $tester->getDisplay());
            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        } finally {
            $this->removeDirectory($emptyDir);
        }
    }

    // ── singleton scope ────────────────────────────────────────────────────

    public function testSingletonScopeShown(): void
    {
        $this->writeService('Demo', 'Cache', 'demo.cache', 'singleton', []);

        $output = $this->runCommand()->getDisplay();
        self::assertStringContainsString('singleton', $output);
    }

    // ── helpers ───────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(array $input = []): CommandTester
    {
        $app = new Application('nqphp');
        $app->add(new ServiceListCommand(new Kernel($this->projectDir)));
        $tester = new CommandTester($app->find('service:list'));
        $tester->execute($input);
        return $tester;
    }

    /**
     * @param string[] $tags
     */
    private function writeService(
        string $feature,
        string $className,
        string $name,
        string $scope,
        array $tags,
    ): void {
        $dir = $this->projectDir . "/src/Feature/$feature/Service";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $tagsLiteral = '[' . implode(', ', array_map(fn(string $t) => "'$t'", $tags)) . ']';
        $ns = 'Nqphp\\CommandFixture\\' . $feature;
        $php = <<<PHP
<?php
namespace $ns;

use Nqphp\\Core\\Attribute\\Service;

#[Service(name: '$name', scope: '$scope', tags: $tagsLiteral)]
final class $className {}
PHP;
        file_put_contents("$dir/$className.php", $php);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
