<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates password strength: length, mixed case, digits, and special characters.
 */
class PasswordStrengthRule implements RuleInterface
{
    public function __construct(
        private readonly int $minLength = 8,
        private readonly bool $requireMixedCase = true,
        private readonly bool $requireDigits = true,
        private readonly bool $requireSpecialChars = true
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (mb_strlen($value) < $this->minLength) {
            return false;
        }

        if ($this->requireMixedCase && (!preg_match('/[a-z]/', $value) || !preg_match('/[A-Z]/', $value))) {
            return false;
        }

        if ($this->requireDigits && !preg_match('/[0-9]/', $value)) {
            return false;
        }

        if ($this->requireSpecialChars && !preg_match('/[^a-zA-Z0-9]/', $value)) {
            return false;
        }

        return true;
    }

    public function message(string $field): string
    {
        return "Поле {$field} не соответствует требованиям надежности пароля.";
    }
}
