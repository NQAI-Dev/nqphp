<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class FeatureShowCommandTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/nqphp-feature-command-' . bin2hex(random_bytes(6));
        mkdir($this->projectDir . '/src/Feature/Blog', 0777, true);
        file_put_contents(
            $this->projectDir . '/src/Feature/Blog/config.php',
            "<?php\nreturn ['enabled' => true, 'cache_ttl' => 120, 'hosts' => ['api.example.test']];\n"
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testCommandFailsForFeatureWithoutConfiguration(): void
    {
        mkdir($this->projectDir . '/src/Feature/Empty');
        $tester = $this->runCommand('Empty');

        self::assertStringContainsString('not discovered', $tester->getDisplay());
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    public function testCommandReportsActualConfigurationValues(): void
    {
        $tester = $this->runCommand('Blog');
        $output = $tester->getDisplay();

        self::assertStringContainsString('Feature: Blog', $output);
        self::assertStringContainsString('Configuration', $output);
        self::assertStringContainsString('cache_ttl', $output);
        self::assertStringContainsString('120', $output);
        self::assertStringContainsString('enabled', $output);
        self::assertStringContainsString('true', $output);
        self::assertStringContainsString('["api.example.test"]', $output);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    private function runCommand(string $name): CommandTester
    {
        $app = new Application('nqphp');
        $app->add(new \Nqphp\Core\Console\FeatureShowCommand(new Kernel($this->projectDir)));
        $tester = new CommandTester($app->find('feature:show'));
        $tester->execute(['name' => $name]);

        return $tester;
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
