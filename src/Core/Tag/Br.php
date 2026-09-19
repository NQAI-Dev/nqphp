<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Br extends AbstractTag
{
    protected string $tag = 'br';
    protected bool $selfClosing = true;
}
