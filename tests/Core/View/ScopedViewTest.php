<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\View;

use Nqphp\Core\I18n\TranslatorInterface;
use Nqphp\Core\View\ScopedView;
use PHPUnit\Framework\TestCase;

class ScopedViewTest extends TestCase
{
    public function testRenderBasicScopedView(): void
    {
        $view = new ScopedView('<h1>Hello World</h1>', 'h1 { color: red; }');
        $html = $view->toHtml();

        $this->assertStringContainsString('<style data-nq-scope-style="s-', $html);
        $this->assertStringContainsString('<div data-nq-s="s-', $html);
        $this->assertStringContainsString('<h1>Hello World</h1>', $html);
        $this->assertSame($html, (string) $view);
    }

    public function testRenderWithoutCss(): void
    {
        $view = ScopedView::create('<p>Simple paragraph</p>');
        $html = $view->toHtml();

        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringContainsString('<div data-nq-s="s-', $html);
        $this->assertStringContainsString('<p>Simple paragraph</p>', $html);
    }

    public function testRenderDoesNotDoubleWrapIfAttributePresent(): void
    {
        $customHtml = '<section data-nq-s="custom-scope"><span>Text</span></section>';
        $view = new ScopedView($customHtml, '', null, 'custom-scope');
        $html = $view->toHtml();

        $this->assertSame($customHtml, $html);
    }

    public function testRenderTranslations(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(function (string $key, array $params = []): string {
            if ($key === 'welcome.user') {
                return 'Welcome, ' . ($params['name'] ?? 'Guest') . '!';
            }
            return $key;
        });

        $markup = '<p>{{ t("welcome.user", ["name" => "Alice"]) }}</p>';
        $view = new ScopedView($markup, '', $translator);

        $html = $view->toHtml();
        $this->assertStringContainsString('Welcome, Alice!', $html);
    }

    public function testMethodTWithoutTranslatorReturnsKey(): void
    {
        $view = new ScopedView('<div></div>');
        $this->assertSame('messages.greeting', $view->t('messages.greeting'));
    }
}
