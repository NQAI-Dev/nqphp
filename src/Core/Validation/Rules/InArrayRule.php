<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value exists within a specified list of allowed values.
 */
class InArrayRule implements RuleInterface
{
    /**
     * @param array<int, mixed> $allowed
     * @param bool $strict Whether to use strict comparison (===)
     */
    public function __construct(
        private readonly array $allowed,
        private readonly bool $strict = false
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        return in_array($value, $this->allowed, $this->strict);
    }

    public function message(string $field): string
    {
        $allowedStr = implode(', ', array_map(
            static fn ($val) => is_scalar($val) ? (string) $val : gettype($val),
            $this->allowed
        ));

        return "Значение поля {$field} должно быть одним из: {$allowedStr}.";
    }
}
