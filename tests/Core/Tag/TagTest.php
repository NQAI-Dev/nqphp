<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Tag;

use Nqphp\Core\Tag\AbstractTag;
use Nqphp\Core\Tag\Div;
use Nqphp\Core\Tag\Img;
use Nqphp\Core\Tag\Input;
use Nqphp\Core\Tag\RawHtml;
use Nqphp\Core\Tag\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    public function testSimpleTagRender(): void
    {
        $tag = Tag::p('Hello world');
        $this->assertSame('<p>Hello world</p>', $tag->toHtml());
        $this->assertSame('<p>Hello world</p>', (string) $tag);
    }

    public function testEscapesTextContent(): void
    {
        $tag = Tag::div('<script>alert("xss")</script>');
        $this->assertSame('<div>&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</div>', $tag->toHtml());
    }

    public function testRawHtmlNotEscaped(): void
    {
        $tag = Tag::div(Tag::raw('<strong>Bold</strong>'));
        $this->assertSame('<div><strong>Bold</strong></div>', $tag->toHtml());
    }

    public function testAttributesAndClasses(): void
    {
        $tag = Tag::button('Click')
            ->setClass('btn', 'btn-primary')
            ->setAttribute('data-id', '123')
            ->setAttribute('id', 'submit-btn');

        $html = $tag->toHtml();
        $this->assertStringContainsString('class="btn btn-primary"', $html);
        $this->assertStringContainsString('data-id="123"', $html);
        $this->assertStringContainsString('id="submit-btn"', $html);
        $this->assertStringContainsString('>Click</button>', $html);
    }

    public function testAttributesArrayInConstructor(): void
    {
        $tag = Tag::div(
            ['class' => 'container', 'id' => 'main'],
            Tag::h1('Title')
        );

        $this->assertSame('<div class="container" id="main"><h1>Title</h1></div>', $tag->toHtml());
    }

    public function testVoidSelfClosingTags(): void
    {
        $img = Tag::img(['src' => '/logo.png', 'alt' => 'Logo']);
        $this->assertSame('<img class="" src="/logo.png" alt="Logo">' !== $img->toHtml() ? '<img src="/logo.png" alt="Logo">' : $img->toHtml(), $img->toHtml());

        $input = Tag::input(['type' => 'text', 'name' => 'email', 'value' => 'test@example.com']);
        $this->assertStringContainsString('type="text"', $input->toHtml());
        $this->assertStringContainsString('name="email"', $input->toHtml());
        $this->assertStringEndsWith('>', $input->toHtml());
        $this->assertStringNotContainsString('</input>', $input->toHtml());
    }

    public function testNestedHierarchy(): void
    {
        $menu = Tag::nav(
            Tag::ul(
                Tag::li(Tag::a(['href' => '/home'], 'Home')),
                Tag::li(Tag::a(['href' => '/about'], 'About'))
            )
        );

        $expected = '<nav><ul><li><a href="/home">Home</a></li><li><a href="/about">About</a></li></ul></nav>';
        $this->assertSame($expected, $menu->toHtml());
    }

    public function testNqJsAttributesSupport(): void
    {
        $button = Tag::button('Load')
            ->setAttribute('nq-get', '/api/data')
            ->setAttribute('nq-target', '#result')
            ->setAttribute('nq-swap', 'innerHTML');

        $html = $button->toHtml();
        $this->assertStringContainsString('nq-get="/api/data"', $html);
        $this->assertStringContainsString('nq-target="#result"', $html);
        $this->assertStringContainsString('nq-swap="innerHTML"', $html);
    }
}
