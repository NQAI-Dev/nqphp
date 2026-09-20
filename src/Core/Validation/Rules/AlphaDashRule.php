<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value contains only alpha-numeric characters, dashes, and underscores.
 */
class AlphaDashRule implements RuleInterface
{
    private const PATTERN = '/^[a-zA-Z0-9_-]+$/u';

    public function __construct(
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $str = (string) $value;
        if ($str === '') {
            return false;
        }

        return (bool) preg_match(self::PATTERN, $str);
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} может содержать только буквы, цифры, дефисы и знаки подчеркивания.";
    }
}
