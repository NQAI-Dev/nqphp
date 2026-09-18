<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation;

use Nqphp\Core\Attribute\Assert;
use ReflectionClass;

final class Validator
{
    /**
     * Validates an object based on #[Assert] attributes on its properties.
     * Returns an array of error messages keyed by property name.
     *
     * @param object $data
     * @return array<string, list<string>>
     */
    public function validate(object $data): array
    {
        $errors = [];
        $refClass = new ReflectionClass($data);

        foreach ($refClass->getProperties() as $property) {
            $attributes = $property->getAttributes(Assert::class);
            if (empty($attributes)) {
                continue;
            }

            if (!$property->isInitialized($data)) {
                $value = null;
            } else {
                $value = $property->getValue($data);
            }

            $propertyName = $property->getName();

            foreach ($attributes as $attribute) {
                /** @var Assert $assert */
                $assert = $attribute->newInstance();
                
                $error = $this->applyRule($value, $assert);
                if ($error !== null) {
                    $errors[$propertyName] ??= [];
                    $errors[$propertyName][] = $error;
                }
            }
        }

        return $errors;
    }

    private function applyRule(mixed $value, Assert $assert): ?string
    {
        return match ($assert->rule) {
            'required' => empty($value) && $value !== '0' && $value !== 0 ? ($assert->message ?? 'Field is required') : null,
            'email' => !filter_var($value, FILTER_VALIDATE_EMAIL) && $value !== null && $value !== '' ? ($assert->message ?? 'Invalid email format') : null,
            'min' => (is_string($value) && mb_strlen($value) < (int)$assert->options) || (is_numeric($value) && $value < $assert->options) ? ($assert->message ?? "Minimum value/length is {$assert->options}") : null,
            'max' => (is_string($value) && mb_strlen($value) > (int)$assert->options) || (is_numeric($value) && $value > $assert->options) ? ($assert->message ?? "Maximum value/length is {$assert->options}") : null,
            default => null,
        };
    }
}
