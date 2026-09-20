<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\AssetsBuildCommand;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AssetsBuildCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_assets_build_test_' . uniqid();
        @mkdir($this->tempDir . '/src/Feature/Blog/View', 0777, true);
        @mkdir($this->tempDir . '/public', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testExecuteWithoutAssets(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new AssetsBuildCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Building production assets...', $output);
        $this->assertStringContainsString('No feature assets found to compile.', $output);
    }

    public function testExecuteCompilesAssetsAndOutputsManifest(): void
    {
        $cssFile = $this->tempDir . '/src/Feature/Blog/View/index.scoped.css';
        file_put_contents($cssFile, '.title { color: red; }');

        $jsFile = $this->tempDir . '/src/Feature/Blog/View/blog.js';
        file_put_contents($jsFile, 'console.log("hello");');

        $kernel = new Kernel($this->tempDir);
        $command = new AssetsBuildCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Compiled:', $output);
        $this->assertStringContainsString('Assets build completed.', $output);

        $manifestFile = $this->tempDir . '/public/assets/dist/manifest.json';
        $this->assertFileExists($manifestFile);
        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        $this->assertIsArray($manifest);
        $this->assertNotEmpty($manifest);
    }
}
