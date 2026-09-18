<?php

declare(strict_types=1);

namespace Nqphp\Core\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class Assert
{
    public function __construct(
        public readonly string $rule,
        public readonly ?string $message = null,
        public readonly mixed $options = null,
    ) {
    }
}
