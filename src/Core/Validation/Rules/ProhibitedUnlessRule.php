<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field is prohibited (empty or null) unless another field equals any of the specified values.
 */
class ProhibitedUnlessRule implements RuleInterface
{
    /**
     * @param string $otherField The name of the field to check condition against
     * @param mixed $targetValues Single value or array of values that permit field population
     * @param array<string, mixed> $data Complete input dataset
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly string $otherField,
        private readonly mixed $targetValues,
        private readonly array $data,
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        $otherVal = $this->data[$this->otherField] ?? null;
        $targets = is_array($this->targetValues) ? $this->targetValues : [$this->targetValues];

        $isAllowed = in_array($otherVal, $targets, true);

        if ($isAllowed) {
            return true;
        }

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
        return $this->customMessage ?? "Поле {$field} запрещено для заполнения, если {$this->otherField} не соответствует разрешенному значению.";
    }
}
