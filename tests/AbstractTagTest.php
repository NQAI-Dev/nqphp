<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Tag\A;
use Nqphp\Core\Tag\Div;
use Nqphp\Core\Tag\Span;
use Nqphp\Core\Tag\Tag;
use PHPUnit\Framework\TestCase;

/**
 * Phase 2 (HTML tag builder) test.
 *
 * Validates the fluent API + HTML escape semantics across Div,
 * Span, A and the Tag static factory.
 */
final class AbstractTagTest extends TestCase
{
    public function testBasicDivRendering(): void
    {
        $div = (new Div())->setContent('hello');
        self::assertSame('<div>hello</div>', $div->toHtml());
    }

    public function testSetClassRendersClassAttribute(): void
    {
        $div = (new Div())->setClass('container', 'row')->setContent('hi');
        self::assertSame('<div class="container row">hi</div>', $div->toHtml());
    }

    public function testSetClassDeduplicates(): void
    {
        $div = (new Div())->setClass('a', 'b', 'a', 'c', 'b')->setContent('x');
        self::assertSame('<div class="a b c">x</div>', $div->toHtml());
    }

    public function testSetClassMergesWithClassAttribute(): void
    {
        // If the caller sets a class attribute, the setClass() calls
        // merge with it (callers wins on order — last in wins).
        $div = (new Div())
            ->setClass('from-setClass')
            ->setAttribute('class', 'from-attribute')
            ->setContent('x');
        self::assertSame('<div class="from-attribute">x</div>', $div->toHtml());
    }

    public function testAttributesAreRenderedInDeclarationOrder(): void
    {
        $div = (new Div())
            ->setAttribute('id', 'main')
            ->setAttribute('data-x', '1')
            ->setContent('x');
        self::assertSame('<div id="main" data-x="1">x</div>', $div->toHtml());
    }

    public function testToStringMagicMatchesToHtml(): void
    {
        $div = (new Div())->setContent('hello');
        self::assertSame($div->toHtml(), (string) $div);
    }

    public function testAttributeValuesAreEscaped(): void
    {
        $div = (new Div())->setAttribute('title', 'He said "hi" & left')->setContent('x');
        self::assertStringContainsString('&quot;', $div->toHtml());
        self::assertStringContainsString('&amp;', $div->toHtml());
    }

    public function testContentIsEscaped(): void
    {
        $div = (new Div())->setContent('<script>alert(1)</script>');
        $html = $div->toHtml();
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testSpan(): void
    {
        $span = (new Span())->setClass('icon')->setContent('X');
        self::assertSame('<span class="icon">X</span>', $span->toHtml());
    }

    public function testAnchorWithStaticFactory(): void
    {
        $a = A::to('https://example.com', 'click me');
        self::assertSame('<a href="https://example.com">click me</a>', $a->toHtml());
    }

    public function testTagStaticFactory(): void
    {
        self::assertInstanceOf(Div::class, Tag::div());
        self::assertInstanceOf(Span::class, Tag::span());
        self::assertInstanceOf(A::class, Tag::a());
    }
}

class VoidTagsTest extends \PHPUnit\Framework\TestCase
{
    public function testImgRendersAsSelfClosing(): void
    {
        $img = (new \Nqphp\Core\Tag\Img())
            ->setAttribute('src', 'avatar.png')
            ->setAttribute('alt', 'Profile photo');
        self::assertSame('<img src="avatar.png" alt="Profile photo">', $img->toHtml());
    }

    public function testImgToShortcut(): void
    {
        $img = \Nqphp\Core\Tag\Img::to('avatar.png', 'Profile photo');
        self::assertSame('<img src="avatar.png" alt="Profile photo">', $img->toHtml());
    }

    public function testBrRendersAsSelfClosing(): void
    {
        $br = (new \Nqphp\Core\Tag\Br())->setClass('spacer');
        self::assertSame('<br class="spacer">', $br->toHtml());
    }

    public function testHrRendersAsSelfClosing(): void
    {
        $hr = (new \Nqphp\Core\Tag\Hr())->setAttribute('data-section', 'end');
        self::assertSame('<hr data-section="end">', $hr->toHtml());
    }

    public function testTagFactoryHasVoidElements(): void
    {
        self::assertInstanceOf(\Nqphp\Core\Tag\Img::class, \Nqphp\Core\Tag\Tag::img());
        self::assertInstanceOf(\Nqphp\Core\Tag\Br::class, \Nqphp\Core\Tag\Tag::br());
        self::assertInstanceOf(\Nqphp\Core\Tag\Hr::class, \Nqphp\Core\Tag\Tag::hr());
    }
}
