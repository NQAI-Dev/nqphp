<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation;

use ReflectionClass;
use ReflectionProperty;

class Validator
{
    private array $errors = [];

    /**
     * Validates an associative array of data against a set of rules,
     * or validates an object (DTO) using #[Assert] attributes.
     * Rules format: ['field' => 'required|email', 'age' => 'min:18|max:100']
     */
    public function validate(array|object $data, array $rules = []): array
    {
        $this->errors = [];

        if (is_object($data)) {
            $this->validateObject($data);
        } else {
            $this->validateArray($data, $rules);
        }

        return $this->errors;
    }

    private function validateObject(object $obj): void
    {
        $ref = new ReflectionClass($obj);
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $field = $prop->getName();
            $value = $prop->isInitialized($obj) ? $prop->getValue($obj) : null;
            
            foreach ($prop->getAttributes(\Nqphp\Core\Attribute\Assert::class) as $attr) {
                $assert = $attr->newInstance();
                $rule = $assert->rule;
                if ($assert->options !== null) {
                    $rule .= ':' . $assert->options;
                }
                
                if ($value === null || $value === '') {
                    if ($rule === 'required') {
                        $this->addError($field, "The {$field} field is required.");
                    }
                    continue;
                }
                
                $this->applyRule($field, $value, $rule);
            }
        }
    }

    private function validateArray(array $data, array $rules): void
    {
        foreach ($rules as $field => $ruleString) {
            $ruleSet = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($ruleSet as $rule) {
                if ($value === null || $value === '') {
                    if ($rule === 'required') {
                        $this->addError($field, "The {$field} field is required.");
                    }
                    continue;
                }

                $this->applyRule($field, $value, $rule);
            }
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        if ($rule === 'required') {
            if ($value === null || $value === '') {
                $this->addError($field, "The {$field} field is required.");
            }
        } elseif ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, "Invalid email format");
            }
        } elseif ($rule === 'numeric') {
            if (!is_numeric($value)) {
                $this->addError($field, "The {$field} field must be a number.");
            }
        } elseif (str_starts_with($rule, 'min:')) {
            $min = (float) substr($rule, 4);
            if (is_numeric($value)) {
                if ($value < $min) {
                    $this->addError($field, "Minimum value/length is {$min}");
                }
            } elseif (is_string($value)) {
                if (strlen($value) < $min) {
                    $this->addError($field, "Minimum value/length is {$min}");
                }
            }
        } elseif (str_starts_with($rule, 'max:')) {
            $max = (float) substr($rule, 4);
            if (is_numeric($value)) {
                if ($value > $max) {
                    $this->addError($field, "Maximum value/length is {$max}");
                }
            } elseif (is_string($value)) {
                if (strlen($value) > $max) {
                    $this->addError($field, "Maximum value/length is {$max}");
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
