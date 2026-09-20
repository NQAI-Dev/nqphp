<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value consists solely of digits with an exact or bounded length.
 */
class DigitsRule implements RuleInterface
{
    /**
     * @param ?int $length Exact length if set
     * @param ?int $min Minimum length if bounded
     * @param ?int $max Maximum length if bounded
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly ?int $length = null,
        private readonly ?int $min = null,
        private readonly ?int $max = null,
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        $str = (string) $value;
        if (!ctype_digit($str)) {
            return false;
        }

        $len = strlen($str);

        if ($this->length !== null && $len !== $this->length) {
            return false;
        }

        if ($this->min !== null && $len < $this->min) {
            return false;
        }

        if ($this->max !== null && $len > $this->max) {
            return false;
        }

        return true;
    }

    public function message(string $field): string
    {
        if ($this->customMessage !== null) {
            return $this->customMessage;
        }

        if ($this->length !== null) {
            return "Поле {$field} должно состоять ровно из {$this->length} цифр.";
        }

        if ($this->min !== null && $this->max !== null) {
            return "Поле {$field} должно содержать от {$this->min} до {$this->max} цифр.";
        }

        if ($this->min !== null) {
            return "Поле {$field} должно содержать не менее {$this->min} цифр.";
        }

        if ($this->max !== null) {
            return "Поле {$field} должно содержать не более {$this->max} цифр.";
        }

        return "Поле {$field} должно содержать только цифры.";
    }
}
