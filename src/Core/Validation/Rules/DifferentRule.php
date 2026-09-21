<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value is different from another field in the data array.
 */
class DifferentRule implements RuleInterface
{
    /**
     * @param string $otherField The name of the field to compare against
     * @param array<string, mixed> $data Complete input dataset
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly string $otherField,
        private readonly array $data,
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!array_key_exists($this->otherField, $this->data)) {
            return true;
        }

        return $value !== $this->data[$this->otherField];
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Значение поля {$field} должно отличаться от поля {$this->otherField}.";
    }
}
