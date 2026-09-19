<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Hr extends AbstractTag
{
    protected string $tag = 'hr';
    protected bool $selfClosing = true;
}
