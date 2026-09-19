<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\View\ViewRenderer;
use PHPUnit\Framework\TestCase;

class ViewRendererTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_view_' . uniqid();
        mkdir($this->tempDir);
        file_put_contents($this->tempDir . '/test.php', 'Hello <?= $name ?>!');
    }

    protected function tearDown(): void
    {
        unlink($this->tempDir . '/test.php');
        rmdir($this->tempDir);
    }

    public function testRender(): void
    {
        $renderer = new ViewRenderer($this->tempDir);
        $this->assertSame('Hello World!', $renderer->render('test.php', ['name' => 'World']));
    }

    public function testRenderThrowsIfNotFound(): void
    {
        $renderer = new ViewRenderer($this->tempDir);
        $this->expectException(\RuntimeException::class);
        $renderer->render('missing.php');
    }
}
