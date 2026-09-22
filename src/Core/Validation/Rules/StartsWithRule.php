<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value starts with one of the given prefixes.
 */
class StartsWithRule implements RuleInterface
{
    /**
     * @param string|string[] $prefixes
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly string|array $prefixes,
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $str = (string) $value;
        $prefixes = is_array($this->prefixes) ? $this->prefixes : [$this->prefixes];

        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($str, (string) $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function message(string $field): string
    {
        if ($this->customMessage !== null) {
            return $this->customMessage;
        }

        $prefixes = is_array($this->prefixes) ? $this->prefixes : [$this->prefixes];
        $expected = implode(', ', array_map(fn ($p) => "'{$p}'", $prefixes));

        return "Значение поля {$field} должно начинаться с одного из префиксов: {$expected}.";
    }
}
