<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value ends with one of the given suffixes.
 */
class EndsWithRule implements RuleInterface
{
    /**
     * @param string|string[] $suffixes
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly string|array $suffixes,
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $str = (string) $value;
        $suffixes = is_array($this->suffixes) ? $this->suffixes : [$this->suffixes];

        foreach ($suffixes as $suffix) {
            if ($suffix !== '' && str_ends_with($str, (string) $suffix)) {
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

        $suffixes = is_array($this->suffixes) ? $this->suffixes : [$this->suffixes];
        $expected = implode(', ', array_map(fn($s) => "'{$s}'", $suffixes));

        return "Значение поля {$field} должно заканчиваться одним из суффиксов: {$expected}.";
    }
}
