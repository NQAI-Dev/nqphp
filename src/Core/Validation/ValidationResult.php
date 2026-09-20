<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation;

/**
 * Encapsulates the outcome of a validation pass.
 */
class ValidationResult
{
    /**
     * @param array<string, list<string>> $errors Map of field name to error message list
     * @param array<string, mixed>|object $data Validated input data
     */
    public function __construct(
        private readonly array $errors = [],
        private readonly array|object $data = []
    ) {
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function isInvalid(): bool
    {
        return !$this->isValid();
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasError(string $field): bool
    {
        return !empty($this->errors[$field]);
    }

    /**
     * @return list<string>
     */
    public function getErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    public function firstError(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|object
     */
    public function validated(): array|object
    {
        return $this->data;
    }
}
