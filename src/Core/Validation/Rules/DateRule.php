<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use DateTimeImmutable;
use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value matches a specified date format.
 */
class DateRule implements RuleInterface
{
    /**
     * @param string $format Expected date/time format (default 'Y-m-d')
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly string $format = 'Y-m-d',
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        $trimmed = trim($value);
        $date = DateTimeImmutable::createFromFormat('!' . $this->format, $trimmed);

        return $date !== false && $date->format($this->format) === $trimmed;
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} должно соответствовать формату даты {$this->format}.";
    }
}
