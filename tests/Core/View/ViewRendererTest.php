<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\View;

use Nqphp\Core\View\ViewRenderer;
use PHPUnit\Framework\TestCase;

class ViewRendererTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_view_renderer_' . uniqid();
        @mkdir($this->tempDir, 0777, true);
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

    public function testRenderSuccessfulTemplate(): void
    {
        $template = $this->tempDir . '/hello.php';
        file_put_contents($template, 'Hello, <?= htmlspecialchars($name, ENT_QUOTES, \'UTF-8\') ?>!');

        $renderer = new ViewRenderer($this->tempDir);
        $output = $renderer->render('hello.php', ['name' => 'Alice']);

        $this->assertSame('Hello, Alice!', $output);
    }

    public function testRenderNestedTemplate(): void
    {
        @mkdir($this->tempDir . '/sub/dir', 0777, true);
        $template = $this->tempDir . '/sub/dir/item.php';
        file_put_contents($template, 'Item: <?= $id ?>');

        $renderer = new ViewRenderer($this->tempDir);
        $output = $renderer->render('/sub/dir/item.php', ['id' => 42]);

        $this->assertSame('Item: 42', $output);
    }

    public function testRenderMissingViewThrowsException(): void
    {
        $renderer = new ViewRenderer($this->tempDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('View not found');
        $renderer->render('non_existent.php');
    }

    public function testRenderCatchesExceptionAndCleansBuffer(): void
    {
        $template = $this->tempDir . '/failing.php';
        file_put_contents($template, 'Output before error. <?php throw new \DomainException("Template exploded"); ?>');

        $renderer = new ViewRenderer($this->tempDir);
        $initialLevel = ob_get_level();

        try {
            $renderer->render('failing.php');
            $this->fail('Expected exception was not thrown');
        } catch (\DomainException $e) {
            $this->assertSame('Template exploded', $e->getMessage());
            $this->assertSame($initialLevel, ob_get_level(), 'Output buffer must be cleaned on error');
        }
    }
}
