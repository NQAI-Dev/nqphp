<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Strong extends AbstractTag
{
    protected string $tag = 'strong';
    protected bool $selfClosing = false;
}
