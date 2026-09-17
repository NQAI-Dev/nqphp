<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

/**
 * Anchor (<a>) tag — used so often it's worth a typed class.
 *
 * Convenience:
 *   echo A::to('https://example.com', 'link text');
 */
final class A extends AbstractTag
{
    public function __construct()
    {
        $this->tag = 'a';
    }

    /** Convenience: href + content in one call. */
    public static function to(string $href, string $content = ''): static
    {
        $a = new static();
        return $a->setAttribute('href', $href)->setContent($content);
    }
}
