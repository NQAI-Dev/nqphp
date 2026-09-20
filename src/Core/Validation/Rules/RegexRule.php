<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that a field matches a specified regular expression pattern.
 */
class RegexRule implements RuleInterface
{
    /**
     * @param string $pattern Regular expression pattern (including delimiters)
     * @param string|null $customMessage Optional custom error message
     */
    public function __construct(
        private readonly string $pattern,
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return (bool) preg_match($this->pattern, (string) $value);
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} имеет недопустимый формат.";
    }
}
