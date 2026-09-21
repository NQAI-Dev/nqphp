<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field is prohibited (must be empty, null, or not present in the dataset).
 */
class ProhibitedRule implements RuleInterface
{
    /**
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && count($value) === 0) {
            return true;
        }

        return false;
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} запрещено для заполнения.";
    }
}
