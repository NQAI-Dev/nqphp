<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field is accepted (yes, on, 1, true, "1") if another field equals any specified target value.
 */
class AcceptedIfRule implements RuleInterface
{
    /**
     * @var list<mixed>
     */
    private const ACCEPTABLE_VALUES = [
        'yes',
        'on',
        '1',
        1,
        true,
        'true',
    ];

    /**
     * @param string $otherField Field name to check condition against
     * @param mixed $targetValues Value or array of values that trigger acceptance requirement
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

        $isConditionMet = in_array($otherVal, $targets, true);

        if (!$isConditionMet) {
            return true;
        }

        return in_array($value, self::ACCEPTABLE_VALUES, true);
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} должно быть принято при значении {$this->otherField}.";
    }
}
