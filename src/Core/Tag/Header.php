<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Header extends AbstractTag
{
    protected string $tag = 'header';
    protected bool $selfClosing = false;
}
