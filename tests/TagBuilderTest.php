<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Tag\Tag;
use PHPUnit\Framework\TestCase;

class TagBuilderTest extends TestCase
{
    public function testVariadicChildrenAndText(): void
    {
        $html = (string) Tag::div(
            Tag::h1('Title'),
            Tag::p('First paragraph'),
            Tag::span('Badge')
        );

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<p>First paragraph</p>', $html);
        $this->assertStringContainsString('<span>Badge</span>', $html);
    }

    public function testFluentHelpers(): void
    {
        $btn = Tag::button('Click me')
            ->class('btn', 'btn-primary')
            ->id('submit-btn')
            ->attr('type', 'submit')
            ->data('role', 'action')
            ->style('margin: 10px;');

        $html = (string) $btn;
        $this->assertStringContainsString('class="btn btn-primary"', $html);
        $this->assertStringContainsString('id="submit-btn"', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('data-role="action"', $html);
        $this->assertStringContainsString('style="margin: 10px;"', $html);
        $this->assertStringContainsString('Click me', $html);
    }

    public function testNqJsIntegration(): void
    {
        $card = Tag::div(
            Tag::button('Load')
                ->nqGet('/api/items')
                ->nqTarget('#feed')
                ->nqSwap('beforeend')
                ->nqIndicator('#spinner')
        );

        $html = (string) $card;
        $this->assertStringContainsString('data-nq-get="/api/items"', $html);
        $this->assertStringContainsString('data-nq-target="#feed"', $html);
        $this->assertStringContainsString('data-nq-swap="beforeend"', $html);
        $this->assertStringContainsString('data-nq-indicator="#spinner"', $html);
    }

    public function testConditionalRenderingWhen(): void
    {
        $cardTrue = Tag::div(Tag::span('Always'))
            ->when(true, fn ($t) => $t->append(Tag::span('Admin Only')));

        $cardFalse = Tag::div(Tag::span('Always'))
            ->when(false, fn ($t) => $t->append(Tag::span('Admin Only')));

        $this->assertStringContainsString('Admin Only', (string) $cardTrue);
        $this->assertStringNotContainsString('Admin Only', (string) $cardFalse);
    }
}
