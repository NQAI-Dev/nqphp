<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value does not exist within a specified list of prohibited values.
 */
class NotInArrayRule implements RuleInterface
{
    /**
     * @param array<int, mixed> $disallowed
     * @param bool $strict Whether to use strict comparison (===)
     */
    public function __construct(
        private readonly array $disallowed,
        private readonly bool $strict = false
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        return !in_array($value, $this->disallowed, $this->strict);
    }

    public function message(string $field): string
    {
        $disallowedStr = implode(', ', array_map(
            static fn ($val) => is_scalar($val) ? (string) $val : gettype($val),
            $this->disallowed
        ));

        return "Поле {$field} не должно содержать любое из следующих значений: {$disallowedStr}.";
    }
}
