<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

use Nqphp\Core\Tag\AbstractTag;

/**
 * Built-in Popover / Popup notification & tooltip component with scoped CSS,
 * anchor positioning, close controls, and nq.js integration.
 */
class Popup extends ScopedView
{
    private string $id;
    private string $content;
    private string $placement;
    private ?string $triggerId;
    private bool $dismissible;

    public function __construct(
        string $id,
        string|AbstractTag $content,
        string $placement = 'bottom',
        ?string $triggerId = null,
        bool $dismissible = true
    ) {
        $this->id = htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->content = $content instanceof AbstractTag ? $content->toHtml() : (string)$content;
        $this->placement = in_array($placement, ['top', 'bottom', 'left', 'right'], true) ? $placement : 'bottom';
        $this->triggerId = $triggerId !== null ? htmlspecialchars($triggerId, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
        $this->dismissible = $dismissible;

        $html = $this->buildMarkup();
        $css = $this->buildStyles();

        parent::__construct($html, $css, null, 'nq-popup-' . $this->id);
    }

    public static function make(
        string $id,
        string|AbstractTag $content,
        string $placement = 'bottom',
        ?string $triggerId = null,
        bool $dismissible = true
    ): static {
        return new static($id, $content, $placement, $triggerId, $dismissible);
    }

    public function getId(): string
    {
        return $this->id;
    }

    private function buildMarkup(): string
    {
        $triggerAttr = $this->triggerId !== null ? sprintf('data-anchor="%s"', $this->triggerId) : '';
        $closeButton = $this->dismissible
            ? sprintf('<button type="button" class="nq-popup-close" onclick="document.getElementById(\'%s\').remove()" aria-label="Close">&times;</button>', $this->id)
            : '';

        $dismissAttr = $this->dismissible ? 'data-nq-dismissible="true"' : '';

        return <<<HTML
<div id="{$this->id}" class="nq-popup nq-popup-{$this->placement}" {$triggerAttr} {$dismissAttr} role="tooltip">
    <div class="nq-popup-arrow"></div>
    <div class="nq-popup-inner">
        <div class="nq-popup-content">{$this->content}</div>
        {$closeButton}
    </div>
</div>
<script>
(function() {
    var p = document.getElementById('{$this->id}');
    if (!p) return;
    function onDocClick(e) {
        if (!p.isConnected) {
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
    padding: 0.65rem 0.85rem;
    position: relative;
    z-index: 2;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}
.nq-popup-content {
    flex: 1;
}
.nq-popup-close {
    background: transparent;
    border: none;
    color: #a6adc8;
    font-size: 1.15rem;
    line-height: 1;
    cursor: pointer;
    padding: 0 0.2rem;
    border-radius: 4px;
    transition: color 0.15s, background 0.15s;
    margin: -0.2rem -0.2rem 0 0;
}
.nq-popup-close:hover {
    color: #f38ba8;
    background: rgba(243, 139, 168, 0.1);
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
