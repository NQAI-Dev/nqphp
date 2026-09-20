<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

use Nqphp\Core\Tag\AbstractTag;

/**
 * Built-in Popover / Popup notification & tooltip component with scoped CSS,
 * anchor positioning, autohide, and nq.js integration.
 */
class Popup extends ScopedView
{
    private string $id;
    private string $content;
    private string $placement;
    private ?string $triggerId;

    public function __construct(
        string $id,
        string|AbstractTag $content,
        string $placement = 'bottom',
        ?string $triggerId = null
    ) {
        $this->id = htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->content = $content instanceof AbstractTag ? $content->toHtml() : (string)$content;
        $this->placement = in_array($placement, ['top', 'bottom', 'left', 'right'], true) ? $placement : 'bottom';
        $this->triggerId = $triggerId !== null ? htmlspecialchars($triggerId, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;

        $html = $this->buildMarkup();
        $css = $this->buildStyles();

        parent::__construct($html, $css, null, 'nq-popup-' . $this->id);
    }

    public static function make(
        string $id,
        string|AbstractTag $content,
        string $placement = 'bottom',
        ?string $triggerId = null
    ): static {
        return new static($id, $content, $placement, $triggerId);
    }

    public function getId(): string
    {
        return $this->id;
    }

    private function buildMarkup(): string
    {
        $triggerAttr = $this->triggerId !== null ? sprintf('data-anchor="%s"', $this->triggerId) : '';

        return <<<HTML
<div id="{$this->id}" class="nq-popup nq-popup-{$this->placement}" {$triggerAttr} role="tooltip">
    <div class="nq-popup-arrow"></div>
    <div class="nq-popup-inner">
        {$this->content}
    </div>
</div>
HTML;
    }

    private function buildStyles(): string
    {
        return <<<'CSS'
.nq-popup {
    position: absolute;
    z-index: 9998;
    background: #181825;
    color: #cdd6f4;
    border: 1px solid #313244;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -4px rgba(0, 0, 0, 0.4);
    font-size: 0.875rem;
    line-height: 1.5;
    animation: nq-popup-pop 0.15s ease-out;
}
.nq-popup-inner {
    padding: 0.75rem 1rem;
    position: relative;
    z-index: 2;
}
.nq-popup-arrow {
    position: absolute;
    width: 8px;
    height: 8px;
    background: #181825;
    border: 1px solid #313244;
    transform: rotate(45deg);
    z-index: 1;
}
.nq-popup-bottom > .nq-popup-arrow {
    top: -5px;
    left: 50%;
    margin-left: -4px;
    border-bottom: none;
    border-right: none;
}
.nq-popup-top > .nq-popup-arrow {
    bottom: -5px;
    left: 50%;
    margin-left: -4px;
    border-top: none;
    border-left: none;
}
.nq-popup-left > .nq-popup-arrow {
    right: -5px;
    top: 50%;
    margin-top: -4px;
    border-left: none;
    border-bottom: none;
}
.nq-popup-right > .nq-popup-arrow {
    left: -5px;
    top: 50%;
    margin-top: -4px;
    border-top: none;
    border-right: none;
}
@keyframes nq-popup-pop {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
CSS;
    }
}
