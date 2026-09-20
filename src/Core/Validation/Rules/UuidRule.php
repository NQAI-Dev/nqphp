<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the given field is a valid UUID (versions 1-5 or generic).
 */
class UuidRule implements RuleInterface
{
    /**
     * @param int|null $version Specific UUID version (1-5), or null for any valid UUID.
     */
    public function __construct(
        private readonly ?int $version = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if ($this->version !== null) {
            $pattern = match ($this->version) {
                1 => '/^[0-9a-f]{8}-[0-9a-f]{4}-1[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                2 => '/^[0-9a-f]{8}-[0-9a-f]{4}-2[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                3 => '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                4 => '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                5 => '/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                default => null,
            };

            if ($pattern === null) {
                return false;
            }

            return (bool) preg_match($pattern, $value);
        }

        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }

    public function message(string $field): string
    {
        if ($this->version !== null) {
            return "Поле {$field} должно быть корректным UUID версии {$this->version}.";
        }

        return "Поле {$field} должно быть корректным UUID.";
    }
}
