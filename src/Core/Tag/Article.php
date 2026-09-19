<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class Article extends AbstractTag
{
    protected string $tag = 'article';
    protected bool $selfClosing = false;
}
