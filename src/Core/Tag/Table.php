<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Table extends AbstractTag
{
    protected string $tag = 'table';
    protected bool $selfClosing = false;
}
