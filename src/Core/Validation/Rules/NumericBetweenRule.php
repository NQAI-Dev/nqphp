<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that a numeric field value falls within the specified range [min, max].
 */
class NumericBetweenRule implements RuleInterface
{
    public function __construct(
        private readonly float|int $min,
        private readonly float|int $max
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $num = is_string($value) ? (str_contains($value, '.') ? (float) $value : (int) $value) : $value;

        return $num >= $this->min && $num <= $this->max;
    }

    public function message(string $field): string
    {
        return "Значение поля {$field} должно быть в диапазоне от {$this->min} до {$this->max}.";
    }
}
