<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Section extends AbstractTag
{
    protected string $tag = 'section';
    protected bool $selfClosing = false;
}
