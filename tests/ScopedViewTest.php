<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\I18n\Translator;
use Nqphp\Core\View\ScopedView;
use PHPUnit\Framework\TestCase;

final class ScopedViewTest extends TestCase
{
    private string $tempDir;
    private Translator $translator;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_view_i18n_' . uniqid();
        mkdir($this->tempDir . '/en', 0777, true);
        mkdir($this->tempDir . '/ru', 0777, true);

        file_put_contents($this->tempDir . '/en/app.php', '<?php return [
            "card_title" => "User Dashboard",
            "greeting" => "Hello, :user!"
        ];');

        file_put_contents($this->tempDir . '/ru/app.php', '<?php return [
            "card_title" => "Панель управления",
            "greeting" => "Привет, :user!"
        ];');

        $this->translator = new Translator($this->tempDir, 'en', 'en');
    }

    protected function tearDown(): void
    {
        @unlink($this->tempDir . '/en/app.php');
        @unlink($this->tempDir . '/ru/app.php');
        @rmdir($this->tempDir . '/en');
        @rmdir($this->tempDir . '/ru');
        @rmdir($this->tempDir);
    }

    public function testScopedViewWithCssAndTranslations(): void
    {
        $html = '<div class="user-card"><h3>{{ t("app.card_title") }}</h3><p>{{ t("app.greeting", ["user" => "Alex"]) }}</p></div>';
        $css = 'h3 { color: #38bdf8; } .user-card { padding: 1rem; }';

        $view = new ScopedView($html, $css, $this->translator);
        $rendered = $view->toHtml();

        $this->assertStringContainsString('data-nq-s=', $rendered);
        $this->assertStringContainsString('User Dashboard', $rendered);
        $this->assertStringContainsString('Hello, Alex!', $rendered);
        $this->assertStringContainsString('color:#38bdf8', $rendered);

        // Switch to Russian locale
        $this->translator->setLocale('ru');
        $renderedRu = $view->toHtml();
        $this->assertStringContainsString('Панель управления', $renderedRu);
        $this->assertStringContainsString('Привет, Alex!', $renderedRu);
    }
}
