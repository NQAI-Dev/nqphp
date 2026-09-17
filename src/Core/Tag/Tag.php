<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

/**
 * Static factory for typed HTML tag builders.
 *
 * Usage:
 *   echo Tag::div()->setClass('container')->setContent('hi');
 *   echo Tag::span()->setContent('inline');
 *   echo Tag::a('/about', 'About');
 *
 * New HTML elements can be added by adding a static method here
 * once the concrete subclass ships. No magic __call — explicit
 * factory methods give IDE autocomplete and static analysis.
 */
final class Tag
{
    public static function div(): Div
    {
        return new Div();
    }

    public static function span(): Span
    {
        return new Span();
    }

    public static function a(): A
    {
        return new A();
    }
}
