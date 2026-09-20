<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation;

use ReflectionClass;
use ReflectionProperty;

class Validator
{
    private array $errors = [];

    /** @var array<string, callable(mixed, string): string|null> */
    private array $customRules = [];

    /**
     * Validates an associative array of data against a set of rules,
     * or validates an object (DTO) using #[Assert] attributes.
     * Rules format: ['field' => 'required|email', 'age' => 'min:18|max:100']
     *
     * Built-in rules:
     *   required, email, url, numeric, integer, boolean, alpha, alpha_num,
     *   min:<n>, max:<n>, between:<min>,<max>,
     *   in:<a>,<b>,<c>, not_in:<a>,<b>,<c>,
     *   regex:<pattern>, date, date_format:<format>,
     *   same:<field>, different:<field>
     *
     * Custom rules: registered via addRule().
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

    /**
     * Register a custom validation rule.
     *
     * The callable receives ($value, $field) and must return null on pass
     * or an error message string on failure.
     *
     * @param callable(mixed, string): string|null $handler
     */
    public function addRule(string $name, callable $handler): static
    {
        $this->customRules[$name] = $handler;
        return $this;
    }

    public function passes(array|object $data, array $rules = []): bool
    {
        return $this->validate($data, $rules) === [];
    }

    public function fails(array|object $data, array $rules = []): bool
    {
        return !$this->passes($data, $rules);
    }

    /**
     * Validate data and throw ValidationException if errors exist.
     * Returns the validated data or object on success.
     *
     * @template T of array|object
     * @param T $data
     * @param array<string, string> $rules
     * @return T
     * @throws ValidationException
     */
    public function validateOrThrow(array|object $data, array $rules = []): array|object
    {
        $errors = $this->validate($data, $rules);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    // ─── Object (DTO) validation ──────────────────────────────────────────────

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

                $this->applyRule($field, $value, $rule, []);
            }
        }
    }

    // ─── Array validation ─────────────────────────────────────────────────────

    private function validateArray(array $data, array $rules): void
    {
        foreach ($rules as $field => $ruleString) {
            $ruleSet = explode('|', $ruleString);
            $value   = $data[$field] ?? null;

            foreach ($ruleSet as $rule) {
                if ($value === null || $value === '') {
                    if ($rule === 'required') {
                        $this->addError($field, "The {$field} field is required.");
                    }
                    continue;
                }

                $this->applyRule($field, $value, $rule, $data);
            }
        }
    }

    // ─── Rule dispatcher ──────────────────────────────────────────────────────

    private function applyRule(string $field, mixed $value, string $rule, array $data): void
    {
        // Split rule name from options: "min:8", "in:a,b,c", "regex:/^\d+$/"
        $colonPos  = strpos($rule, ':');
        $ruleName  = $colonPos !== false ? substr($rule, 0, $colonPos) : $rule;
        $ruleParam = $colonPos !== false ? substr($rule, $colonPos + 1) : '';

        // Custom rules take priority
        if (isset($this->customRules[$ruleName])) {
            $message = ($this->customRules[$ruleName])($value, $field);
            if ($message !== null) {
                $this->addError($field, $message);
            }
            return;
        }

        match ($ruleName) {
            'required'    => $this->ruleRequired($field, $value),
            'email'       => $this->ruleEmail($field, $value),
            'url'         => $this->ruleUrl($field, $value),
            'numeric'     => $this->ruleNumeric($field, $value),
            'integer'     => $this->ruleInteger($field, $value),
            'boolean'     => $this->ruleBoolean($field, $value),
            'alpha'       => $this->ruleAlpha($field, $value),
            'alpha_num'   => $this->ruleAlphaNum($field, $value),
            'min'         => $this->ruleMin($field, $value, (float) $ruleParam),
            'max'         => $this->ruleMax($field, $value, (float) $ruleParam),
            'between'     => $this->ruleBetween($field, $value, $ruleParam),
            'in'          => $this->ruleIn($field, $value, $ruleParam),
            'not_in'      => $this->ruleNotIn($field, $value, $ruleParam),
            'regex'       => $this->ruleRegex($field, $value, $ruleParam),
            'date'        => $this->ruleDate($field, $value),
            'date_format' => $this->ruleDateFormat($field, $value, $ruleParam),
            'same'        => $this->ruleSame($field, $value, $ruleParam, $data),
            'different'   => $this->ruleDifferent($field, $value, $ruleParam, $data),
            'uuid'        => $this->ruleUuid($field, $value),
            'json'        => $this->ruleJson($field, $value),
            'ip'          => $this->ruleIp($field, $value),
            default       => null,   // unknown rule — silently skip
        };
    }

    // ─── Built-in rule implementations ───────────────────────────────────────

    private function ruleRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->addError($field, "The {$field} field is required.");
        }
    }

    private function ruleEmail(string $field, mixed $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} field must be a valid email address.");
        }
    }

    private function ruleUrl(string $field, mixed $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "The {$field} field must be a valid URL.");
        }
    }

    private function ruleNumeric(string $field, mixed $value): void
    {
        if (!is_numeric($value)) {
            $this->addError($field, "The {$field} field must be a number.");
        }
    }

    private function ruleInteger(string $field, mixed $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "The {$field} field must be an integer.");
        }
    }

    private function ruleBoolean(string $field, mixed $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $this->addError($field, "The {$field} field must be a boolean value.");
        }
    }

    private function ruleAlpha(string $field, mixed $value): void
    {
        if (!is_string($value) || !ctype_alpha($value)) {
            $this->addError($field, "The {$field} field may only contain letters.");
        }
    }

    private function ruleAlphaNum(string $field, mixed $value): void
    {
        if (!is_string($value) || !ctype_alnum($value)) {
            $this->addError($field, "The {$field} field may only contain letters and numbers.");
        }
    }

    private function ruleMin(string $field, mixed $value, float $min): void
    {
        $numeric = is_numeric($value);
        if ($numeric && (float) $value < $min) {
            $this->addError($field, "The {$field} field must be at least {$min}.");
            return;
        }
        if (!$numeric && is_string($value) && strlen($value) < (int) $min) {
            $this->addError($field, "The {$field} field must be at least {$min} characters.");
        }
    }

    private function ruleMax(string $field, mixed $value, float $max): void
    {
        $numeric = is_numeric($value);
        if ($numeric && (float) $value > $max) {
            $this->addError($field, "The {$field} field may not be greater than {$max}.");
            return;
        }
        if (!$numeric && is_string($value) && strlen($value) > (int) $max) {
            $this->addError($field, "The {$field} field may not be greater than {$max} characters.");
        }
    }

    private function ruleBetween(string $field, mixed $value, string $param): void
    {
        [$min, $max] = array_map('floatval', explode(',', $param, 2));
        $numeric = is_numeric($value);
        if ($numeric) {
            $v = (float) $value;
            if ($v < $min || $v > $max) {
                $this->addError($field, "The {$field} field must be between {$min} and {$max}.");
            }
            return;
        }
        if (is_string($value)) {
            $len = strlen($value);
            if ($len < (int) $min || $len > (int) $max) {
                $this->addError($field, "The {$field} field must be between {$min} and {$max} characters.");
            }
        }
    }

    private function ruleIn(string $field, mixed $value, string $param): void
    {
        $allowed = explode(',', $param);
        if (!in_array((string) $value, $allowed, true)) {
            $this->addError($field, "The {$field} field must be one of: {$param}.");
        }
    }

    private function ruleNotIn(string $field, mixed $value, string $param): void
    {
        $forbidden = explode(',', $param);
        if (in_array((string) $value, $forbidden, true)) {
            $this->addError($field, "The {$field} field must not be one of: {$param}.");
        }
    }

    private function ruleRegex(string $field, mixed $value, string $pattern): void
    {
        if (!is_string($value) || !preg_match($pattern, $value)) {
            $this->addError($field, "The {$field} field format is invalid.");
        }
    }

    private function ruleDate(string $field, mixed $value): void
    {
        if (!is_string($value) || strtotime($value) === false) {
            $this->addError($field, "The {$field} field must be a valid date.");
        }
    }

    private function ruleDateFormat(string $field, mixed $value, string $format): void
    {
        if (!is_string($value)) {
            $this->addError($field, "The {$field} field must be a date string.");
            return;
        }
        $dt = \DateTimeImmutable::createFromFormat($format, $value);
        if ($dt === false || $dt->format($format) !== $value) {
            $this->addError($field, "The {$field} field must match the format {$format}.");
        }
    }

    private function ruleSame(string $field, mixed $value, string $otherField, array $data): void
    {
        if (!array_key_exists($otherField, $data) || $value !== $data[$otherField]) {
            $this->addError($field, "The {$field} field must match {$otherField}.");
        }
    }

    private function ruleDifferent(string $field, mixed $value, string $otherField, array $data): void
    {
        if (array_key_exists($otherField, $data) && $value === $data[$otherField]) {
            $this->addError($field, "The {$field} field must be different from {$otherField}.");
        }
    }

    private function ruleUuid(string $field, mixed $value): void
    {
        if (!is_string($value) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            $this->addError($field, "The {$field} field must be a valid UUID.");
        }
    }

    private function ruleJson(string $field, mixed $value): void
    {
        if (!is_string($value)) {
            $this->addError($field, "The {$field} field must be a valid JSON string.");
            return;
        }

        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError($field, "The {$field} field must be a valid JSON string.");
        }
    }

    private function ruleIp(string $field, mixed $value): void
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_IP) === false) {
            $this->addError($field, "The {$field} field must be a valid IP address.");
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
