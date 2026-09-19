<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

/**
 * Static factory for typed HTML tag builders with flexible constructor.
 *
 * Usage:
 *   // Simple child / text:
 *   Tag::p('Hello world');
 *   Tag::button('Submit')->class('btn', 'btn-primary');
 *
 *   // Nested elements (variadic):
 *   Tag::div(
 *       Tag::h1('Dashboard'),
 *       Tag::p('Welcome back!'),
 *       Tag::button('Action')->nqGet('/api/ping')->nqTarget('#res')
 *   )->class('card');
 *
 *   // With attributes array:
 *   Tag::div(['class' => 'container', 'id' => 'app'],
 *       Tag::span('Loaded')
 *   );
 */
final class Tag
{
    public static function div(mixed ...$args): Div
    {
        return new Div(...$args);
    }

    public static function h1(mixed ...$args): H1
    {
        return new H1(...$args);
    }

    public static function h2(mixed ...$args): H2
    {
        return new H2(...$args);
    }

    public static function h3(mixed ...$args): H3
    {
        return new H3(...$args);
    }

    public static function h4(mixed ...$args): H4
    {
        return new H4(...$args);
    }

    public static function p(mixed ...$args): P
    {
        return new P(...$args);
    }

    public static function span(mixed ...$args): Span
    {
        return new Span(...$args);
    }

    public static function pre(mixed ...$args): Pre
    {
        return new Pre(...$args);
    }

    public static function code(mixed ...$args): Code
    {
        return new Code(...$args);
    }

    public static function ul(mixed ...$args): Ul
    {
        return new Ul(...$args);
    }

    public static function li(mixed ...$args): Li
    {
        return new Li(...$args);
    }

    public static function a(mixed ...$args): A
    {
        return new A(...$args);
    }

    public static function img(mixed ...$args): Img
    {
        return new Img(...$args);
    }

    public static function br(mixed ...$args): Br
    {
        return new Br(...$args);
    }

    public static function hr(mixed ...$args): Hr
    {
        return new Hr(...$args);
    }

    public static function input(mixed ...$args): Input
    {
        return new Input(...$args);
    }

    public static function textarea(mixed ...$args): Textarea
    {
        return new Textarea(...$args);
    }

    public static function label(mixed ...$args): Label
    {
        return new Label(...$args);
    }

    public static function select(mixed ...$args): Select
    {
        return new Select(...$args);
    }

    public static function option(mixed ...$args): Option
    {
        return new Option(...$args);
    }

    public static function button(mixed ...$args): Button
    {
        return new Button(...$args);
    }

    public static function form(mixed ...$args): Form
    {
        return new Form(...$args);
    }

    public static function table(mixed ...$args): Table
    {
        return new Table(...$args);
    }

    public static function thead(mixed ...$args): Thead
    {
        return new Thead(...$args);
    }

    public static function tbody(mixed ...$args): Tbody
    {
        return new Tbody(...$args);
    }

    public static function tr(mixed ...$args): Tr
    {
        return new Tr(...$args);
    }

    public static function th(mixed ...$args): Th
    {
        return new Th(...$args);
    }

    public static function td(mixed ...$args): Td
    {
        return new Td(...$args);
    }

    public static function nav(mixed ...$args): Nav
    {
        return new Nav(...$args);
    }

    public static function header(mixed ...$args): Header
    {
        return new Header(...$args);
    }

    public static function footer(mixed ...$args): Footer
    {
        return new Footer(...$args);
    }

    public static function main(mixed ...$args): Main
    {
        return new Main(...$args);
    }

    public static function section(mixed ...$args): Section
    {
        return new Section(...$args);
    }

    public static function article(mixed ...$args): Article
    {
        return new Article(...$args);
    }

    public static function aside(mixed ...$args): Aside
    {
        return new Aside(...$args);
    }


    /**
     * Render raw unescaped HTML.
     */
    public static function raw(string $html): RawHtml
    {
        return new RawHtml($html);
    }

}
