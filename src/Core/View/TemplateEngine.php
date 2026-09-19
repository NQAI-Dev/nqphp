<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

use RuntimeException;

/**
 * Native PHP template engine with layout inheritance and named block slots.
 *
 * Usage in templates:
 *   $this->layout('layouts/base.php', ['title' => 'Page']);
 *   $this->block('content'); ... $this->endBlock();
 *
 * Usage in layouts:
 *   <?= $this->slot('content') ?>
 */
class TemplateEngine implements TemplateEngineInterface
{
    private string $viewsDir;

    /** @var array<string, string> Currently accumulated named blocks */
    private array $blocks = [];

    /** @var string|null Pending block name being captured */
    private ?string $currentBlock = null;

    /** @var array<string, mixed>|null Pending layout data [template, context] */
    private ?array $pendingLayout = null;

    /** @var array<string, mixed> Current render context */
    private array $context = [];

    public function __construct(string $viewsDir)
    {
        $this->viewsDir = rtrim($viewsDir, '/');
    }

    public function supports(string $template): bool
    {
        return str_ends_with($template, '.php') || !str_contains($template, '.');
    }

    public function render(string $template, array $context = []): string
    {
        $path = $this->resolvePath($template);
        $this->context = $context;
        $this->blocks = [];
        $this->pendingLayout = null;

        $output = $this->renderFile($path, $context);

        // If template declared a layout, render the layout now
        if ($this->pendingLayout !== null) {
            [$layoutTemplate, $layoutContext] = $this->pendingLayout;
            $this->pendingLayout = null;
            $layoutPath = $this->resolvePath($layoutTemplate);
            $output = $this->renderFile($layoutPath, array_merge($context, $layoutContext));
        }

        return $output;
    }

    /**
     * Called from inside a template to declare a parent layout.
     *
     * @param array<string, mixed> $extraContext
     */
    public function layout(string $template, array $extraContext = []): void
    {
        $this->pendingLayout = [$template, $extraContext];
    }

    /**
     * Start capturing a named block.
     */
    public function block(string $name): void
    {
        if ($this->currentBlock !== null) {
            throw new RuntimeException(sprintf('Cannot start block "%s" while block "%s" is still open.', $name, $this->currentBlock));
        }
        $this->currentBlock = $name;
        ob_start();
    }

    /**
     * End the current block and save its content.
     */
    public function endBlock(): void
    {
        if ($this->currentBlock === null) {
            throw new RuntimeException('endBlock() called without a matching block().');
        }
        $this->blocks[$this->currentBlock] = (string) ob_get_clean();
        $this->currentBlock = null;
    }

    /**
     * Output a named block slot (used in layouts). Returns the block content or default.
     */
    public function slot(string $name, string $default = ''): string
    {
        return $this->blocks[$name] ?? $default;
    }

    /**
     * Escape a value for safe HTML output.
     */
    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function resolvePath(string $template): string
    {
        // If the template has no extension, add .php
        if (!str_contains(basename($template), '.')) {
            $template .= '.php';
        }
        $path = $this->viewsDir . '/' . ltrim($template, '/');
        if (!file_exists($path)) {
            throw new RuntimeException(sprintf('Template not found: %s', $path));
        }
        return $path;
    }

    /**
     * Render a single file with isolated variable scope.
     *
     * @param array<string, mixed> $data
     */
    private function renderFile(string $path, array $data): string
    {
        $data['engine'] = $this;
        $renderFunc = static function (string $__path, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                include $__path;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            return (string) ob_get_clean();
        };

        return $renderFunc($path, $data);
    }
}
