<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field must be present in the input data, even if it is null or empty.
 */
class PresentRule implements RuleInterface
{
    /**
     * @param array<string, mixed> $data Complete input dataset
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly array $data,
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        return array_key_exists($field, $this->data);
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} обязательно должно присутствовать в запросе.";
    }
}
