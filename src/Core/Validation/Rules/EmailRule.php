<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value is a valid email address.
 */
class EmailRule implements RuleInterface
{
    public function __construct(
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        return filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false;
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} должно содержать корректный email-адрес.";
    }
}
