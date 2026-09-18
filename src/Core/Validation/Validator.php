<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation;

class Validator
{
    private array $errors = [];

    /**
     * Validates an associative array of data against a set of rules.
     * Rules format: ['field' => 'required|email', 'age' => 'min:18|max:100']
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleSet = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($ruleSet as $rule) {
                // If field is not required and value is null/empty string, skip other rules
                if ($value === null || $value === '') {
                    if ($rule === 'required') {
                        $this->addError($field, "The {$field} field is required.");
                    }
                    continue; // Skip further checks if empty, unless 'required' caught it
                }

                $this->applyRule($field, $value, $rule);
            }
        }

        return count($this->errors) === 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        if ($rule === 'required') {
            // Already handled in validate(), but kept for logic separation
            if ($value === null || $value === '') {
                $this->addError($field, "The {$field} field is required.");
            }
        } elseif ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, "The {$field} field must be a valid email address.");
            }
        } elseif ($rule === 'numeric') {
            if (!is_numeric($value)) {
                $this->addError($field, "The {$field} field must be a number.");
            }
        } elseif (str_starts_with($rule, 'min:')) {
            $min = (float) substr($rule, 4);
            if (is_numeric($value)) {
                if ($value < $min) {
                    $this->addError($field, "The {$field} field must be at least {$min}.");
                }
            } elseif (is_string($value)) {
                if (strlen($value) < $min) {
                    $this->addError($field, "The {$field} field must be at least {$min} characters long.");
                }
            }
        } elseif (str_starts_with($rule, 'max:')) {
            $max = (float) substr($rule, 4);
            if (is_numeric($value)) {
                if ($value > $max) {
                    $this->addError($field, "The {$field} field must not exceed {$max}.");
                }
            } elseif (is_string($value)) {
                if (strlen($value) > $max) {
                    $this->addError($field, "The {$field} field must not exceed {$max} characters.");
                }
            }
        }
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
}
