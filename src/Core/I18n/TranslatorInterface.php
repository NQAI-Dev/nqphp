<?php

declare(strict_types=1);

namespace Nqphp\Core\I18n;

interface TranslatorInterface
{
    /**
     * Translate a given key with optional parameters and locale override.
     *
     * @param string $key Dotted translation key (e.g., "messages.welcome")
     * @param array<string, mixed> $parameters Parameters to substitute (:param or {param})
     * @param string|null $locale Specific locale to translate to, or current locale if null
     * @return string
     */
    public function trans(string $key, array $parameters = [], ?string $locale = null): string;

    /**
     * Get the active locale code (e.g., 'en', 'ru').
     */
    public function getLocale(): string;

    /**
     * Set the active locale code.
     */
    public function setLocale(string $locale): void;

    /**
     * Get the fallback locale.
     */
    public function getFallbackLocale(): string;

    /**
     * Check if a translation key exists for a locale.
     */
    public function has(string $key, ?string $locale = null): bool;
}
