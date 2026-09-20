<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field matches its confirmation counterpart (e.g. password matches password_confirmation).
 */
class ConfirmedRule implements RuleInterface
{
    /**
     * @param array<string, mixed> $allData Complete dataset being validated
     * @param string|null $confirmationField Custom confirmation field name (defaults to {field}_confirmation)
     */
    public function __construct(
        private readonly array $allData,
        private readonly ?string $confirmationField = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        $confirmationKey = $this->confirmationField ?? ($field . '_confirmation');

        if (!array_key_exists($confirmationKey, $this->allData)) {
            return false;
        }

        return $value === $this->allData[$confirmationKey];
    }

    public function message(string $field): string
    {
        return "Поле {$field} не совпадает с полем подтверждения.";
    }
}
