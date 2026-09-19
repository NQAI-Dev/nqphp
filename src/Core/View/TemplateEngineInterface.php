<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

/**
 * Interface for the template engine.
 */
interface TemplateEngineInterface
{
    /**
     * Render a template with the given context variables.
     *
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string;

    /**
     * Whether the engine can handle the given template name.
     */
    public function supports(string $template): bool;
}
