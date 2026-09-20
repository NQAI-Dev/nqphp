<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\View;

use InvalidArgumentException;
use Nqphp\Core\View\PhpTemplateEngine;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PhpTemplateEngineTest extends TestCase
{
    private string $tempDir;
    private PhpTemplateEngine $engine;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_view_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->engine = new PhpTemplateEngine($this->tempDir);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    public function testSupportsPhpTemplates(): void
    {
        $this->assertTrue($this->engine->supports('index.php'));
        $this->assertTrue($this->engine->supports('nested/page.php'));
        $this->assertFalse($this->engine->supports('index.html'));
        $this->assertFalse($this->engine->supports('index.twig'));
    }

    public function testRenderRendersTemplateWithContext(): void
    {
        file_put_contents($this->tempDir . '/hello.php', 'Hello, <?= $name ?>! Count: <?= $count ?>');

        $result = $this->engine->render('hello.php', ['name' => 'Alice', 'count' => 42]);
        $this->assertSame('Hello, Alice! Count: 42', $result);
    }

    public function testThrowsExceptionOnUnsupportedTemplateExtension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->render('index.html');
    }

    public function testThrowsExceptionOnMissingFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->engine->render('missing.php');
    }
}
