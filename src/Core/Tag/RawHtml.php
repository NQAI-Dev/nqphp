<?php

declare(strict_types=1);

namespace Nqphp\Core\Tag;

final class RawHtml extends AbstractTag
{
    private string $raw;

    public function __construct(string $raw)
    {
        $this->raw = $raw;
    }

    public function toHtml(): string
    {
        return $this->raw;
    }

    public function __toString(): string
    {
        return $this->raw;
    }
}
