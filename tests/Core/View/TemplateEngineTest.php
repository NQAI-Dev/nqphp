<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\View;

use Nqphp\Core\View\TemplateEngine;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TemplateEngineTest extends TestCase
{
    private string $viewsDir;

    protected function setUp(): void
    {
        $this->viewsDir = sys_get_temp_dir() . '/nqphp_view_test/views';
        $this->buildFixtures();
    }

    private function buildFixtures(): void
    {
        $layouts = $this->viewsDir . '/layouts';
        $pages = $this->viewsDir . '/pages';

        @mkdir($layouts, 0775, true);
        @mkdir($pages, 0775, true);

        file_put_contents($layouts . '/base.php', <<<'TMPL'
<!DOCTYPE html>
<html>
<head><title><?= $engine->e($title ?? 'Default') ?></title></head>
<body>
<?= $engine->slot('content', '<p>No content</p>') ?>
<?= $engine->slot('sidebar', '') ?>
</body>
</html>
TMPL);

        file_put_contents($pages . '/hello.php', <<<'TMPL'
<?php $engine->layout('layouts/base.php', ['title' => 'Hello']); ?>
<?php $engine->block('content'); ?>
<h1>Hello, <?= $engine->e($name) ?>!</h1>
<?php $engine->endBlock(); ?>
TMPL);

        file_put_contents($pages . '/simple.php', <<<'TMPL'
<p>Value: <?= $engine->e($value) ?></p>
TMPL);

        file_put_contents($pages . '/twoblocks.php', <<<'TMPL'
<?php $engine->layout('layouts/base.php', ['title' => 'Two']); ?>
<?php $engine->block('content'); ?><main>Main</main><?php $engine->endBlock(); ?>
<?php $engine->block('sidebar'); ?><aside>Aside</aside><?php $engine->endBlock(); ?>
TMPL);

        file_put_contents($pages . '/xss.php', <<<'TMPL'
<p><?= $engine->e($dirty) ?></p>
TMPL);
    }

    public function testSimpleTemplateWithoutLayout(): void
    {
        $engine = new TemplateEngine($this->viewsDir);
        $output = $engine->render('pages/simple.php', ['value' => 'hello world']);

        $this->assertStringContainsString('<p>Value: hello world</p>', $output);
    }

    public function testLayoutInheritanceWithBlock(): void
    {
        $engine = new TemplateEngine($this->viewsDir);
        $output = $engine->render('pages/hello.php', ['name' => 'Alice']);

        $this->assertStringContainsString('<title>Hello</title>', $output);
        $this->assertStringContainsString('<h1>Hello, Alice!</h1>', $output);
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
    }

    public function testDefaultSlotContentWhenBlockMissing(): void
    {
        $engine = new TemplateEngine($this->viewsDir);
        $output = $engine->render('pages/hello.php', ['name' => 'Bob']);

        // sidebar slot not declared — default empty string
        $this->assertStringNotContainsString('<aside>', $output);
    }

    public function testMultipleNamedBlocks(): void
    {
        $engine = new TemplateEngine($this->viewsDir);
        $output = $engine->render('pages/twoblocks.php', []);

        $this->assertStringContainsString('<main>Main</main>', $output);
        $this->assertStringContainsString('<aside>Aside</aside>', $output);
        $this->assertStringContainsString('<title>Two</title>', $output);
    }

    public function testHtmlEscaping(): void
    {
        $engine = new TemplateEngine($this->viewsDir);
        $output = $engine->render('pages/xss.php', ['dirty' => '<script>alert("xss")</script>']);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testThrowsOnMissingTemplate(): void
    {
        $engine = new TemplateEngine($this->viewsDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template not found');

        $engine->render('pages/does_not_exist.php');
    }

    public function testSupportsPhpExtension(): void
    {
        $engine = new TemplateEngine($this->viewsDir);

        $this->assertTrue($engine->supports('page.php'));
        $this->assertFalse($engine->supports('page.twig'));
    }
}
