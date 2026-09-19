<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

use Nqphp\Core\I18n\TranslatorInterface;

/**
 * Scoped HTML component/view supporting:
 * - Scoped CSS with data-nq-s attribute or @scope
 * - Built-in i18n translation interpolation via {{ t('key') }} and programmatic t()
 * - Stringable / toHtml() rendering
 */
class ScopedView implements \Stringable
{
    private string $scopeId;
    private string $html;
    private string $css;
    private ?TranslatorInterface $translator;

    public function __construct(
        string $html,
        string $css = '',
        ?TranslatorInterface $translator = null,
        ?string $scopeId = null
    ) {
        $this->html = $html;
        $this->css = trim($css);
        $this->translator = $translator;
        $this->scopeId = $scopeId ?? ScopedCssCompiler::generateScopeId($this->html . $this->css);
    }

    public static function create(
        string $html,
        string $css = '',
        ?TranslatorInterface $translator = null
    ): static {
        return new static($html, $css, $translator);
    }

    public function getScopeId(): string
    {
        return $this->scopeId;
    }

    /**
     * Translate key with optional parameters and locale.
     *
     * @param array<string, mixed> $params
     */
    public function t(string $key, array $params = [], ?string $locale = null): string
    {
        if ($this->translator === null) {
            return $key;
        }
        return $this->translator->trans($key, $params, $locale);
    }

    /**
     * Render the component with injected scoped CSS and scope attributes.
     */
    public function toHtml(): string
    {
        // 1. Process i18n placeholders like {{ t('messages.key') }} or {{ trans('...') }}
        $content = $this->renderTranslations($this->html);

        // 2. Wrap root container with data-nq-s attribute if not already present
        if (!str_contains($content, 'data-nq-s=')) {
            $content = sprintf('<div data-nq-s="%s">%s</div>', $this->scopeId, $content);
        }

        // 3. Compile and prepend Scoped CSS if present
        if ($this->css !== '') {
            $compiledCss = ScopedCssCompiler::compile($this->css, $this->scopeId);
            $styleTag = sprintf('<style data-nq-scope-style="%s">%s</style>', $this->scopeId, $compiledCss);
            return $styleTag . $content;
        }

        return $content;
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    private function renderTranslations(string $markup): string
    {
        if ($this->translator === null) {
            return $markup;
        }

        // Match {{ t('key') }} or {{ t("key", ["name" => "Val"]) }}
        return preg_replace_callback(
            '/\{\{\s*(?:t|trans)\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*(\[[^\]]+\]))?\s*\)\s*\}\}/',
            function (array $matches): string {
                $key = $matches[1];
                $params = [];
                if (isset($matches[2])) {
                    try {
                        /** @var array<string, mixed> $params */
                        $params = eval('return ' . $matches[2] . ';') ?: [];
                    } catch (\Throwable) {
                        $params = [];
                    }
                }
                return htmlspecialchars($this->t($key, $params), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            },
            $markup
        ) ?? $markup;
    }
}
