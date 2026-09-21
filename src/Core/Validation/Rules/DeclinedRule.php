<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field is "declined" (no, off, 0, false, "0").
 * Useful for opt-out checkboxes, terms rejection, or negative confirmations.
 */
class DeclinedRule implements RuleInterface
{
    /**
     * @var list<mixed>
     */
    private const ACCEPTABLE_VALUES = [
        'no',
        'off',
        '0',
        0,
        false,
        'false',
    ];

    /**
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        return in_array($value, self::ACCEPTABLE_VALUES, true);
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} должно быть отклонено (нет, off, 0 или false).";
    }
}
