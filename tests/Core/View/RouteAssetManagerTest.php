<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\View;

use Nqphp\Core\View\RouteAssetManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class RouteAssetManagerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_asset_manager_' . uniqid();
        @mkdir($this->tempDir . '/public/assets/dist', 0777, true);
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

    public function testRegisterScopedCssFromRawString(): void
    {
        $manager = new RouteAssetManager($this->tempDir);
        $res = $manager->registerScopedCss('.card { padding: 10px; }', 'blog_index');

        $this->assertArrayHasKey('scopeId', $res);
        $this->assertArrayHasKey('assetUrl', $res);
        $this->assertStringStartsWith('s-', $res['scopeId']);
        $this->assertStringStartsWith('/assets/scoped/blog_index.', $res['assetUrl']);

        $generatedFile = $this->tempDir . '/public' . $res['assetUrl'];
        $this->assertFileExists($generatedFile);
        $content = file_get_contents($generatedFile);
        $this->assertStringContainsString('[data-nq-s="' . $res['scopeId'] . '"] .card{padding:10px}', $content);
    }

    public function testRegisterScopedCssUsesManifestWhenAvailable(): void
    {
        $manifest = [
            'blog_index' => '/assets/dist/blog_index.compiled.css',
        ];
        file_put_contents(
            $this->tempDir . '/public/assets/dist/manifest.json',
            json_encode($manifest, JSON_UNESCAPED_SLASHES)
        );

        $manager = new RouteAssetManager($this->tempDir);
        $res = $manager->registerScopedCss('.card { color: red; }', 'blog_index');

        $this->assertSame('/assets/dist/blog_index.compiled.css', $res['assetUrl']);
    }

    public function testAttachToResponseSetsHeaders(): void
    {
        $manager = new RouteAssetManager($this->tempDir);
        $response = new Response('<div>Hello</div>', 200);

        $manager->attachToResponse($response, '/assets/scoped/page.css', '/assets/feature.js');

        $this->assertSame('/assets/scoped/page.css', $response->headers->get('X-NQ-CSS'));
        $this->assertSame('/assets/feature.js', $response->headers->get('X-NQ-JS'));
    }
}
