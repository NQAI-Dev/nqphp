<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

/**
 * Scoped CSS compiler for component styling and HTML DSL.
 *
 * Supports native modern CSS `@scope` with optional automatic
 * deduplication so repeated components only inject one style block.
 */
class ScopedCss
{
    /** @var array<string, bool> Registry of emitted scope hashes in current request */
    private static array $emitted = [];

    /**
     * Clear emitted cache (useful between requests or in unit tests).
     */
    public static function reset(): void
    {
        self::$emitted = [];
    }

    /**
     * Compute a stable short scope ID based on CSS content.
     */
    public static function hash(string $css): string
    {
        return 'nq-s' . substr(hash('xxh128', trim($css)), 0, 8);
    }

    /**
     * Wrap CSS in a modern @scope declaration targeting the scope attribute.
     */
    public static function compile(string $scopeId, string $css): string
    {
        $cleanCss = trim($css);
        if ($cleanCss === '') {
            return '';
        }

        return sprintf(
            "<style>@scope ([data-nq-scope=\"%s\"]) {\n%s\n}</style>",
            $scopeId,
            $cleanCss
        );
    }

    /**
     * Compile CSS only if this exact CSS hash has not been emitted yet.
     */
    public static function compileOnce(string $scopeId, string $css): string
    {
        if (isset(self::$emitted[$scopeId])) {
            return '';
        }

        self::$emitted[$scopeId] = true;
        return self::compile($scopeId, $css);
    }
}
