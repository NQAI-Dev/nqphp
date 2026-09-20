<?php

declare(strict_types=1);

namespace Nqphp\Core\I18n;

final class Translator implements TranslatorInterface
{
    private string $locale;
    private string $fallbackLocale;
    private string $translationsDir;

    /**
     * @var array<string, array<string, mixed>> [locale => [domain => [key => val]]]
     */
    private array $catalogue = [];

    public function __construct(
        string $translationsDir,
        string $defaultLocale = 'en',
        string $fallbackLocale = 'en'
    ) {
        $this->translationsDir = rtrim($translationsDir, '/\\');
        $this->locale = $defaultLocale;
        $this->fallbackLocale = $fallbackLocale;
    }

    public function trans(string $key, array $parameters = [], ?string $locale = null): string
    {
        $targetLocale = $locale ?? $this->locale;
        $message = $this->lookup($key, $targetLocale);

        if ($message === null && $targetLocale !== $this->fallbackLocale) {
            $message = $this->lookup($key, $this->fallbackLocale);
        }

        if ($message === null) {
            return $key;
        }

        if (empty($parameters)) {
            return $message;
        }

        return $this->interpolate($message, $parameters);
    }

    public function transChoice(string $key, int $number, array $parameters = [], ?string $locale = null): string
    {
        $targetLocale = $locale ?? $this->locale;
        $message = $this->lookup($key, $targetLocale);

        if ($message === null && $targetLocale !== $this->fallbackLocale) {
            $message = $this->lookup($key, $this->fallbackLocale);
        }

        if ($message === null) {
            return $key;
        }

        $segments = array_map('trim', explode('|', $message));
        $index = PluralizationRule::getIndex($number, $targetLocale);

        $chosen = $segments[$index] ?? (end($segments) ?: $key);

        $parameters['count'] = $number;

        return $this->interpolate($chosen, $parameters);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    public function has(string $key, ?string $locale = null): bool
    {
        $targetLocale = $locale ?? $this->locale;
        if ($this->lookup($key, $targetLocale) !== null) {
            return true;
        }
        if ($targetLocale !== $this->fallbackLocale) {
            return $this->lookup($key, $this->fallbackLocale) !== null;
        }
        return false;
    }

    private function lookup(string $key, string $locale): ?string
    {
        $this->loadLocale($locale);

        $parts = explode('.', $key);
        if (count($parts) === 1) {
            $domain = 'messages';
            $path = $parts;
        } else {
            $domain = array_shift($parts);
            $path = $parts;
        }

        if (!isset($this->catalogue[$locale][$domain])) {
            return null;
        }

        $current = $this->catalogue[$locale][$domain];
        foreach ($path as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return null;
            }
        }

        return is_string($current) || is_numeric($current) ? (string) $current : null;
    }

    private function loadLocale(string $locale): void
    {
        if (isset($this->catalogue[$locale])) {
            return;
        }

        $this->catalogue[$locale] = [];

        // Check if translations directory exists
        if (!is_dir($this->translationsDir)) {
            return;
        }

        // 1. Check directory structure: translations/{locale}/*.php or translations/*.{locale}.php
        $pattern1 = $this->translationsDir . '/' . $locale . '/*.php';
        foreach (glob($pattern1) ?: [] as $file) {
            $domain = pathinfo($file, PATHINFO_FILENAME);
            $data = require $file;
            if (is_array($data)) {
                $this->catalogue[$locale][$domain] = $data;
            }
        }

        $pattern2 = $this->translationsDir . '/*.' . $locale . '.php';
        foreach (glob($pattern2) ?: [] as $file) {
            $base = pathinfo($file, PATHINFO_FILENAME);
            $domain = substr($base, 0, strrpos($base, '.') ?: null);
            $data = require $file;
            if (is_array($data)) {
                $this->catalogue[$locale][$domain] = array_merge(
                    $this->catalogue[$locale][$domain] ?? [],
                    $data
                );
            }
        }
    }

    private function interpolate(string $message, array $parameters): string
    {
        $replacements = [];
        foreach ($parameters as $key => $value) {
            $val = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
            $replacements[':' . $key] = $val;
            $replacements['{' . $key . '}'] = $val;
        }
        return strtr($message, $replacements);
    }
}
