<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

use Nqphp\Core\Tag\AbstractTag;

/**
 * Built-in Popover / Popup notification & tooltip component with scoped CSS,
 * anchor positioning, close controls, backdrop dismissal, and nq.js integration.
 */
class Popup extends ScopedView
{
    private string $id;
    private string $title;
    private string $content;
    private string $placement;
    private ?string $triggerId;
    private bool $dismissible;

    public function __construct(
        string $id,
        string|AbstractTag $content,
        string $placement = 'bottom',
        ?string $triggerId = null,
        bool $dismissible = true,
        string $title = ''
    ) {
        $this->id = htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->content = $content instanceof AbstractTag ? $content->toHtml() : (string)$content;
        $this->placement = in_array($placement, ['top', 'bottom', 'left', 'right'], true) ? $placement : 'bottom';
        $this->triggerId = $triggerId !== null ? htmlspecialchars($triggerId, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
        $this->dismissible = $dismissible;
        $this->title = htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $html = $this->buildMarkup();
        $css = $this->buildStyles();

        parent::__construct($html, $css, null, 'nq-popup-' . $this->id);
    }

    public static function make(
        string $id,
        string|AbstractTag $content,
        string $placement = 'bottom',
        ?string $triggerId = null,
        bool $dismissible = true,
        string $title = ''
    ): static {
        return new static($id, $content, $placement, $triggerId, $dismissible, $title);
    }

    public function getId(): string
    {
        return $this->id;
    }

    private function buildMarkup(): string
    {
        $triggerAttr = $this->triggerId !== null ? sprintf('data-anchor="%s"', $this->triggerId) : '';
        $closeButton = $this->dismissible
            ? sprintf('<button type="button" class="nq-popup-close" onclick="var p=document.getElementById(\'%s\'); if(p) p.remove();" aria-label="Close">&times;</button>', $this->id)
            : '';

        $titleHtml = $this->title !== ''
            ? sprintf('<div class="nq-popup-header"><span class="nq-popup-title">%s</span>%s</div>', $this->title, $closeButton)
            : '';

        $headerClose = $this->title === '' ? $closeButton : '';
        $dismissAttr = $this->dismissible ? 'data-nq-dismissible="true"' : '';

        return <<<HTML
<div id="{$this->id}" class="nq-popup nq-popup-{$this->placement}" {$triggerAttr} {$dismissAttr} role="tooltip">
    <div class="nq-popup-arrow"></div>
    {$titleHtml}
    <div class="nq-popup-inner">
        <div class="nq-popup-content">{$this->content}</div>
        {$headerClose}
    </div>
</div>
<script>
(function() {
    var p = document.getElementById('{$this->id}');
    if (!p) return;

    // Positioning logic relative to parent container or anchor
    var container = p.parentElement;
    if (container && getComputedStyle(container).position === 'static') {
        container.style.position = 'relative';
    }

    function onDocClick(e) {
        if (!p || !p.isConnected) {
            document.removeEventListener('click', onDocClick);
            return;
        }
        if (!p.contains(e.target)) {
            p.remove();
            document.removeEventListener('click', onDocClick);
        }
    }
    setTimeout(function() {
        document.addEventListener('click', onDocClick);
    }, 50);
})();
</script>
HTML;
    }

    private function buildStyles(): string
    {
        return <<<'CSS'
.nq-popup {
    position: absolute;
    z-index: 9998;
    background: #1e1e2e;
    color: #cdd6f4;
    border: 1px solid #45475a;
    border-radius: 10px;
    box-shadow: 0 16px 32px -4px rgba(0, 0, 0, 0.5), 0 6px 12px -3px rgba(0, 0, 0, 0.4);
    font-size: 0.875rem;
    line-height: 1.5;
    min-width: 260px;
    max-width: 380px;
    width: max-content;
    animation: nq-popup-pop 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
.nq-popup-top {
    bottom: calc(100% + 12px);
    left: 50%;
    transform: translateX(-50%);
}
.nq-popup-bottom {
    top: calc(100% + 12px);
    left: 50%;
    transform: translateX(-50%);
}
.nq-popup-left {
    right: calc(100% + 12px);
    top: 50%;
    transform: translateY(-50%);
}
.nq-popup-right {
    left: calc(100% + 12px);
    top: 50%;
    transform: translateY(-50%);
}
.nq-popup-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.65rem 0.9rem;
    border-bottom: 1px solid #313244;
    background: rgba(24, 24, 37, 0.6);
    border-top-left-radius: 9px;
    border-top-right-radius: 9px;
}
.nq-popup-title {
    font-weight: 600;
    font-size: 0.85rem;
    color: #cdd6f4;
    letter-spacing: 0.01em;
}
.nq-popup-inner {
    padding: 0.85rem 0.95rem;
    position: relative;
    z-index: 2;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}
.nq-popup-content {
    flex: 1;
    color: #a6adc8;
    font-size: 0.85rem;
    line-height: 1.55;
}
.nq-popup-close {
    background: transparent;
    border: none;
    color: #a6adc8;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    padding: 0.15rem 0.35rem;
    border-radius: 4px;
    transition: all 0.15s ease;
}
.nq-popup-close:hover {
    color: #f38ba8;
    background: rgba(243, 139, 168, 0.12);
}
.nq-popup-arrow {
    position: absolute;
    width: 10px;
    height: 10px;
    background: #1e1e2e;
    border: 1px solid #45475a;
    transform: rotate(45deg);
    z-index: 1;
}
.nq-popup-bottom > .nq-popup-arrow {
    top: -6px;
    left: 50%;
    margin-left: -5px;
    border-bottom: none;
    border-right: none;
}
.nq-popup-top > .nq-popup-arrow {
    bottom: -6px;
    left: 50%;
    margin-left: -5px;
    border-top: none;
    border-left: none;
}
.nq-popup-left > .nq-popup-arrow {
    right: -6px;
    top: 50%;
    margin-top: -5px;
    border-left: none;
    border-bottom: none;
}
.nq-popup-right > .nq-popup-arrow {
    left: -6px;
    top: 50%;
    margin-top: -5px;
    border-top: none;
    border-right: none;
}
@keyframes nq-popup-pop {
    from { opacity: 0; transform: translateY(4px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.nq-popup-top {
    animation-name: nq-popup-pop-top;
}
@keyframes nq-popup-pop-top {
    from { opacity: 0; transform: translate(-50%, 4px) scale(0.96); }
    to { opacity: 1; transform: translate(-50%, 0) scale(1); }
}
.nq-popup-bottom {
    animation-name: nq-popup-pop-bottom;
}
@keyframes nq-popup-pop-bottom {
    from { opacity: 0; transform: translate(-50%, -4px) scale(0.96); }
    to { opacity: 1; transform: translate(-50%, 0) scale(1); }
}
CSS;
    }
}
