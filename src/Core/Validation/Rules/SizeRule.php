<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value has an exact size (length for string, count for array, numeric value for int/float).
 */
class SizeRule implements RuleInterface
{
    /**
     * @param int|float $size Expected size
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly int|float $size,
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (is_int($value) || is_float($value)) {
            return $value == $this->size;
        }

        if (is_string($value)) {
            return mb_strlen($value) === (int) $this->size;
        }

        if (is_array($value)) {
            return count($value) === (int) $this->size;
        }

        return false;
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} должно иметь размер/значение {$this->size}.";
    }
}
