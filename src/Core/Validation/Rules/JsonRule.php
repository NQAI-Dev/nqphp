<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value is a valid JSON string.
 */
class JsonRule implements RuleInterface
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

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} должно содержать корректную строку JSON.";
    }
}
