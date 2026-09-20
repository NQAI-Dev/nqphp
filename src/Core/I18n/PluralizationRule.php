<?php

declare(strict_types=1);

namespace Nqphp\Core\I18n;

/**
 * Standard pluralization rules for common locales (en, ru, fr, etc.).
 */
class PluralizationRule
{
    /**
     * Choose the correct plural index for a given count and locale.
     * Index conventions:
     * - English/default: 0 = singular (1), 1 = plural (0, 2+)
     * - Russian/Slavic: 0 = one (1, 21...), 1 = few (2-4, 22-24...), 2 = many (0, 5-20, 25-30...)
     * - French: 0 = singular (0, 1), 1 = plural (2+)
     */
    public static function getIndex(int $number, string $locale): int
    {
        $lang = strtolower(substr($locale, 0, 2));
        $n = abs($number);

        return match ($lang) {
            'ru', 'uk', 'be' => self::slavicRule($n),
            'fr' => ($n <= 1) ? 0 : 1,
            default => ($n === 1) ? 0 : 1,
        };
    }

    private static function slavicRule(int $n): int
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return 0; // 1 яблоко, 21 яблоко
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return 1; // 2 яблока, 23 яблока
        }

        return 2; // 0 яблок, 5 яблок, 11 яблок
    }
}
