<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value is a valid MAC address (supports colon, hyphen, or dot formats).
 */
class MacAddressRule implements RuleInterface
{
    private const PATTERN = '/^((([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2}))|(([0-9A-Fa-f]{4}\.){2}([0-9A-Fa-f]{4})))$/';

    public function __construct(
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        return (bool) preg_match(self::PATTERN, trim($value));
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} должно содержать корректный MAC-адрес.";
    }
}
