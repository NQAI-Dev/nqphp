<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

use Nqphp\Core\Tag\AbstractTag;

/**
 * Built-in Modal dialog component with scoped CSS, backdrop, ESC & outside click close,
 * and nq.js integration.
 */
class Modal extends ScopedView
{
    private string $id;
    private string $title;
    private string $body;
    private string $footer;
    private bool $dismissible;

    public function __construct(
        string $id,
        string $title,
        string|AbstractTag $body,
        string|AbstractTag $footer = '',
        bool $dismissible = true
    ) {
        $this->id = htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->title = htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->body = $body instanceof AbstractTag ? $body->toHtml() : (string)$body;
        $this->footer = $footer instanceof AbstractTag ? $footer->toHtml() : (string)$footer;
        $this->dismissible = $dismissible;

        $html = $this->buildMarkup();
        $css = $this->buildStyles();

        parent::__construct($html, $css, null, 'nq-modal-' . $this->id);
    }

    public static function make(
        string $id,
        string $title,
        string|AbstractTag $body,
        string|AbstractTag $footer = '',
        bool $dismissible = true
    ): static {
        return new static($id, $title, $body, $footer, $dismissible);
    }

    public function getId(): string
    {
        return $this->id;
    }

    private function buildMarkup(): string
    {
        $closeButton = $this->dismissible
            ? sprintf('<button type="button" class="nq-modal-close" onclick="document.getElementById(\'%s\').remove()" aria-label="Close">&times;</button>', $this->id)
            : '';

        $footerSection = $this->footer !== ''
            ? sprintf('<div class="nq-modal-footer">%s</div>', $this->footer)
            : '';

        $backdropDismiss = $this->dismissible
            ? sprintf('onclick="if(event.target === this) this.remove()"')
            : '';

        return <<<HTML
<div id="{$this->id}" class="nq-modal-overlay" {$backdropDismiss} role="dialog" aria-modal="true">
    <div class="nq-modal-dialog">
        <div class="nq-modal-header">
            <h3 class="nq-modal-title">{$this->title}</h3>
            {$closeButton}
        </div>
        <div class="nq-modal-body">
            {$this->body}
        </div>
        {$footerSection}
    </div>
</div>
HTML;
    }

    private function buildStyles(): string
    {
        return <<<'CSS'
.nq-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(4px);
    padding: 1rem;
    animation: nq-fade-in 0.15s ease-out;
}
.nq-modal-dialog {
    background: #1e1e2e;
    color: #cdd6f4;
    border: 1px solid #313244;
    border-radius: 12px;
    width: 100%;
    max-width: 540px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: nq-scale-in 0.15s ease-out;
}
.nq-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #313244;
}
.nq-modal-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 600;
    color: #cdd6f4;
}
.nq-modal-close {
    background: transparent;
    border: none;
    color: #a6adc8;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    transition: color 0.15s, background 0.15s;
}
.nq-modal-close:hover {
    color: #f38ba8;
    background: rgba(243, 139, 168, 0.1);
}
.nq-modal-body {
    padding: 1.25rem;
    overflow-y: auto;
    max-height: 75vh;
    font-size: 0.95rem;
    line-height: 1.6;
}
.nq-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    border-top: 1px solid #313244;
    background: #181825;
}
@keyframes nq-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes nq-scale-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
CSS;
    }
}
