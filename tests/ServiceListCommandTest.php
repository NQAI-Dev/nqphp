<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Kernel\Kernel;
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

    public function testCommandListsDiscoveredServices(): void
    {
        $app = new Application('nqphp');
        $app->add(new \Nqphp\Core\Service\ServiceListCommand(new Kernel($this->projectDir)));
        $tester = new CommandTester($app->find('service:list'));
        $tester->execute([]);
        $output = $tester->getDisplay();

        self::assertStringContainsString('Discovered services', $output);
        self::assertStringContainsString('NAME', $output);
        self::assertStringContainsString('SCOPE', $output);
        self::assertStringContainsString('CLASS', $output);
        self::assertStringContainsString('demo.clock', $output);
        self::assertStringContainsString('prototype', $output);
        self::assertStringContainsString('Nqphp\\CommandFixture\\Clock', $output);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
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
