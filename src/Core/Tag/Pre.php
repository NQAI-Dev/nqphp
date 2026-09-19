<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Pre extends AbstractTag
{
    protected string $tag = 'pre';
    protected bool $selfClosing = false;
}
