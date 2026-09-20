<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the field value is a valid URL with optional scheme filtering.
 */
class UrlRule implements RuleInterface
{
    /**
     * @param array<int, string> $allowedSchemes E.g. ['http', 'https']
     * @param ?string $customMessage
     */
    public function __construct(
        private readonly array $allowedSchemes = ['http', 'https'],
        private readonly ?string $customMessage = null
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        $url = trim($value);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme === null || !is_string($scheme)) {
            return false;
        }

        if (!empty($this->allowedSchemes)) {
            return in_array(strtolower($scheme), array_map('strtolower', $this->allowedSchemes), true);
        }

        return true;
    }

    public function message(string $field): string
    {
        return $this->customMessage ?? "Поле {$field} должно содержать корректный URL-адрес.";
    }
}
