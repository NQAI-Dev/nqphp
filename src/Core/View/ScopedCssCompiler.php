<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

/**
 * Scoped CSS compiler:
 * - Scopes CSS selectors using attribute selectors [data-nq-s="hash"]
 * - Supports :scope pseudo-class -> [data-nq-s="hash"]
 * - Supports keyframes and media queries preservation
 * - Minifies output (strips comments, redundant whitespace)
 */
final class ScopedCssCompiler
{
    /**
     * Compute a deterministic 8-char hash for the scope.
     */
    public static function generateScopeId(string $cssOrPath): string
    {
        return 's-' . substr(hash('xxh128', $cssOrPath), 0, 8);
    }

    /**
     * Compile raw CSS into scoped and minified CSS.
     */
    public static function compile(string $css, string $scopeId): string
    {
        $scopeAttr = sprintf('[data-nq-s="%s"]', $scopeId);

        // 1. Strip CSS comments
        $css = preg_replace('!/\*.*?\*/!s', '', $css) ?? $css;

        // 2. Parse rules, media queries, and keyframes
        $output = '';
        $tokens = self::tokenize($css);

        foreach ($tokens as $token) {
            if ($token['type'] === 'at-rule-block') {
                // e.g. @media (...) { ... }
                $header = trim($token['header']);
                if (str_starts_with($header, '@keyframes')) {
                    // Do not scope keyframe steps (0%, from, to, etc.)
                    $output .= $header . '{' . self::minifyDeclarations($token['content']) . '}';
                } else {
                    // Inner rules of @media, @supports
                    $innerScoped = self::compile($token['content'], $scopeId);
                    $output .= $header . '{' . $innerScoped . '}';
                }
            } elseif ($token['type'] === 'rule') {
                $selectors = explode(',', $token['selector']);
                $scopedSelectors = [];

                foreach ($selectors as $sel) {
                    $sel = trim($sel);
                    if ($sel === '') {
                        continue;
                    }
                    $scopedSelectors[] = self::scopeSelector($sel, $scopeAttr);
                }

                if (!empty($scopedSelectors)) {
                    $declarations = self::minifyDeclarations($token['body']);
                    if ($declarations !== '') {
                        $output .= implode(',', $scopedSelectors) . '{' . $declarations . '}';
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Scope a single CSS selector.
     * Examples:
     * - ":scope" -> '[data-nq-s="s-123"]'
     * - ":scope.active" -> '[data-nq-s="s-123"].active'
     * - "h3" -> 'h3[data-nq-s="s-123"]'
     * - ".btn:hover" -> '.btn[data-nq-s="s-123"]:hover'
     * - "div > span" -> 'div[data-nq-s="s-123"] > span[data-nq-s="s-123"]'
     * - ".card .desc" -> '.card[data-nq-s="s-123"] .desc[data-nq-s="s-123"]'
     */
    public static function scopeSelector(string $selector, string $scopeAttr): string
    {
        $selector = trim($selector);

        if (str_contains($selector, ':scope')) {
            return str_replace(':scope', $scopeAttr, $selector);
        }

        // Split by combinators: space, >, +, ~
        $parts = preg_split('/(\s*[\s>+~]\s*)/', $selector, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return $selector . $scopeAttr;
        }

        $result = '';
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed === '' || in_array($trimmed, ['>', '+', '~'], true)) {
                $result .= $part;
                continue;
            }

            // If it has pseudo-elements (::before, ::after) or pseudo-classes (:hover)
            // attribute must be placed before pseudo-elements/classes:
            // a:hover -> a[data-nq-s="..."]:hover
            // a::after -> a[data-nq-s="..."]::after
            if (preg_match('/^([^:]+)(::?.*)$/', $part, $matches)) {
                $result .= $matches[1] . $scopeAttr . $matches[2];
            } else {
                $result .= $part . $scopeAttr;
            }
        }

        return $result;
    }

    /**
     * Tokenize CSS blocks.
     * @return list<array{type: string, header?: string, content?: string, selector?: string, body?: string}>
     */
    private static function tokenize(string $css): array
    {
        $tokens = [];
        $length = strlen($css);
        $i = 0;

        while ($i < $length) {
            // Skip whitespace
            while ($i < $length && ctype_space($css[$i])) {
                $i++;
            }
            if ($i >= $length) {
                break;
            }

            // Check for @ rules
            if ($css[$i] === '@') {
                $bracePos = strpos($css, '{', $i);
                $semiPos = strpos($css, ';', $i);

                // Simple at-rules like @import or @charset without block
                if ($semiPos !== false && ($bracePos === false || $semiPos < $bracePos)) {
                    $i = $semiPos + 1;
                    continue;
                }

                if ($bracePos !== false) {
                    $header = substr($css, $i, $bracePos - $i);
                    $blockEnd = self::findMatchingBrace($css, $bracePos);
                    $content = substr($css, $bracePos + 1, $blockEnd - $bracePos - 1);

                    $tokens[] = [
                        'type' => 'at-rule-block',
                        'header' => $header,
                        'content' => $content,
                    ];
                    $i = $blockEnd + 1;
                    continue;
                }
            }

            // Normal selector rule
            $bracePos = strpos($css, '{', $i);
            if ($bracePos === false) {
                break;
            }

            $selector = substr($css, $i, $bracePos - $i);
            $blockEnd = self::findMatchingBrace($css, $bracePos);
            $body = substr($css, $bracePos + 1, $blockEnd - $bracePos - 1);

            $tokens[] = [
                'type' => 'rule',
                'selector' => $selector,
                'body' => $body,
            ];

            $i = $blockEnd + 1;
        }

        return $tokens;
    }

    private static function findMatchingBrace(string $s, int $openPos): int
    {
        $depth = 0;
        $len = strlen($s);
        for ($i = $openPos; $i < $len; $i++) {
            if ($s[$i] === '{') {
                $depth++;
            } elseif ($s[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }
        return $len - 1;
    }

    private static function minifyDeclarations(string $declarations): string
    {
        $d = preg_replace('/\s+/', ' ', trim($declarations)) ?? $declarations;
        $d = str_replace(['; ', ' ;', ': ', ' :', ' {', '{ ', ' }', '} '], [';', ';', ':', ':', '{', '{', '}', '}'], $d);
        return trim($d, " \t\n\r;");
    }
}
