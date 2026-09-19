<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Code extends AbstractTag
{
    protected string $tag = 'code';
    protected bool $selfClosing = false;
}
