<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation;

/**
 * Contract for reusable object-oriented validation rules.
 */
interface RuleInterface
{
    /**
     * Determine if the validation rule passes.
     */
    public function passes(mixed $value, string $field): bool;

    /**
     * Get the validation error message.
     */
    public function message(string $field): string;
}
