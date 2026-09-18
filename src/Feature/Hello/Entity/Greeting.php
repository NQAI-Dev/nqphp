<?php

declare(strict_types=1);

namespace Nqphp\Feature\Hello\Entity;

use Nqphp\Core\Attribute\Column;
use Nqphp\Core\Attribute\Entity;
use Nqphp\Core\Attribute\Id;

#[Entity(name: 'greeting')]
class Greeting
{
    #[Id]
    #[Column(type: 'integer')]
    public ?int $id = null;

    #[Column(type: 'string')]
    public string $message = '';
}
