<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\View;

use Nqphp\Core\Tag\Tag;
use Nqphp\Core\View\Modal;
use Nqphp\Core\View\Popup;
use PHPUnit\Framework\TestCase;

class ModalAndPopupTest extends TestCase
{
    public function testModalRenderingAndScopedCss(): void
    {
        $modal = Modal::make(
            id: 'confirm-delete',
            title: 'Delete Item',
            body: Tag::p([], 'Are you sure you want to delete this item?'),
            footer: Tag::button(['class' => 'btn-danger'], 'Confirm')
        );

        $this->assertSame('confirm-delete', $modal->getId());
        $html = $modal->toHtml();

        $this->assertStringContainsString('id="confirm-delete"', $html);
        $this->assertStringContainsString('class="nq-modal-overlay"', $html);
        $this->assertStringContainsString('Delete Item', $html);
        $this->assertStringContainsString('Are you sure you want to delete this item?', $html);
        $this->assertStringContainsString('Confirm', $html);
        $this->assertStringContainsString('<style data-nq-scope-style="nq-modal-confirm-delete">', $html);
        $this->assertStringContainsString('.nq-modal-overlay', $html);
    }

    public function testModalNonDismissible(): void
    {
        $modal = Modal::make(
            id: 'static-modal',
            title: 'Mandatory Notice',
            body: 'Please wait...',
            dismissible: false
        );

        $html = $modal->toHtml();

        $this->assertStringNotContainsString('class="nq-modal-close"', $html);
        $this->assertStringNotContainsString('if(event.target === this)', $html);
    }

    public function testPopupRenderingAndScopedCss(): void
    {
        $popup = Popup::make(
            id: 'user-tooltip',
            content: Tag::span([], 'Online now'),
            placement: 'top',
            triggerId: 'btn-user-status'
        );

        $this->assertSame('user-tooltip', $popup->getId());
        $html = $popup->toHtml();

        $this->assertStringContainsString('id="user-tooltip"', $html);
        $this->assertStringContainsString('class="nq-popup nq-popup-top"', $html);
        $this->assertStringContainsString('data-anchor="btn-user-status"', $html);
        $this->assertStringContainsString('Online now', $html);
        $this->assertStringContainsString('<style data-nq-scope-style="nq-popup-user-tooltip">', $html);
        $this->assertStringContainsString('.nq-popup-arrow', $html);
    }
}
