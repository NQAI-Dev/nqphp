<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Button extends AbstractTag
{
    protected string $tag = 'button';
    protected bool $selfClosing = false;
}
