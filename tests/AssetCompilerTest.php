<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\View\AssetCompiler;
use Nqphp\Core\View\RouteAssetManager;
use PHPUnit\Framework\TestCase;

final class AssetCompilerTest extends TestCase
{
    private string $tempProject;

    protected function setUp(): void
    {
        $this->tempProject = sys_get_temp_dir() . '/nqphp_asset_proj_' . uniqid();
        mkdir($this->tempProject . '/public', 0777, true);
        mkdir($this->tempProject . '/src/Feature/Demo/View', 0777, true);
        mkdir($this->tempProject . '/src/Feature/Demo/Js', 0777, true);

        // Dummy scoped css
        file_put_contents(
            $this->tempProject . '/src/Feature/Demo/View/card.scoped.css',
            '.card { background: #000; color: #fff; }'
        );

        // Dummy js
        file_put_contents(
            $this->tempProject . '/src/Feature/Demo/Js/counter.js',
            "/* counter script */\nfunction count() { return 1; }\n"
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempProject);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testCompileGeneratesAssetsAndManifest(): void
    {
        $compiler = new AssetCompiler($this->tempProject);
        $manifest = $compiler->compile();

        $this->assertNotEmpty($manifest);
        $this->assertArrayHasKey('demo_card', $manifest);
        $this->assertArrayHasKey('counter', $manifest);

        $manifestFile = $this->tempProject . '/public/assets/dist/manifest.json';
        $this->assertFileExists($manifestFile);

        $savedManifest = $compiler->getManifest();
        $this->assertSame($manifest, $savedManifest);

        // Check compiled files on disk
        $compiledCssUrl = $manifest['demo_card'];
        $compiledCssPath = $this->tempProject . '/public' . $compiledCssUrl;
        $this->assertFileExists($compiledCssPath);
        $cssContent = file_get_contents($compiledCssPath);
        $this->assertStringContainsString('[data-nq-s=', $cssContent);

        // Test RouteAssetManager reads from manifest
        $assetManager = new RouteAssetManager($this->tempProject);
        $result = $assetManager->registerScopedCss(
            $this->tempProject . '/src/Feature/Demo/View/card.scoped.css',
            'demo_card'
        );

        $this->assertSame($compiledCssUrl, $result['assetUrl']);
    }
}
