<?php

declare(strict_types=1);

namespace Nqphp\Core\Config;

use RuntimeException;

/**
 * Lightweight environment loader and typed reader (.env parser).
 */
class Env
{
    /** @var array<string, string> */
    private static array $variables = [];

    /**
     * Load environment variables from a .env file.
     */
    public static function load(string $filePath): void
    {
        if (!is_file($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Strip surrounding quotes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            self::$variables[$key] = $value;
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    /**
     * Get an environment variable with typed casting and fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$variables[$key] ?? $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return self::cast((string) $value);
    }

    /**
     * Get a string value or fail if missing.
     */
    public static function getOrFail(string $key): mixed
    {
        $value = self::get($key);
        if ($value === null) {
            throw new RuntimeException("Environment variable [{$key}] is not set.");
        }

        return $value;
    }

    /**
     * Cast raw string value to boolean, null, numeric, or string.
     */
    private static function cast(string $value): mixed
    {
        $lower = strtolower($value);

        return match ($lower) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => is_numeric($value) ? (str_contains($value, '.') ? (float) $value : (int) $value) : $value,
        };
    }

    /**
     * Clear loaded cache.
     */
    public static function reset(): void
    {
        self::$variables = [];
    }
}
