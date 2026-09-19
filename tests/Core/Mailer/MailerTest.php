<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Mailer;

use Nqphp\Core\Mailer\Email;
use Nqphp\Core\Mailer\Mailer;
use Nqphp\Core\Mailer\NullTransport;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    public function testEmailFluentBuilder(): void
    {
        $email = (new Email())
            ->from('alice@example.com')
            ->to('bob@example.com', 'carol@example.com')
            ->cc('dave@example.com')
            ->bcc('eve@example.com')
            ->subject('Hello World')
            ->text('Plain text body')
            ->html('<h1>HTML body</h1>')
            ->header('X-Custom-Header', 'custom-value');

        $this->assertSame('alice@example.com', $email->getFrom());
        $this->assertSame(['bob@example.com', 'carol@example.com'], $email->getTo());
        $this->assertSame(['dave@example.com'], $email->getCc());
        $this->assertSame(['eve@example.com'], $email->getBcc());
        $this->assertSame('Hello World', $email->getSubject());
        $this->assertSame('Plain text body', $email->getTextBody());
        $this->assertSame('<h1>HTML body</h1>', $email->getHtmlBody());
        $this->assertSame(['X-Custom-Header' => 'custom-value'], $email->getHeaders());
    }

    public function testEmailDeduplicatesRecipients(): void
    {
        $email = (new Email())
            ->to('bob@example.com', 'bob@example.com')
            ->cc('dave@example.com', 'dave@example.com')
            ->bcc('eve@example.com', 'eve@example.com');

        $this->assertSame(['bob@example.com'], $email->getTo());
        $this->assertSame(['dave@example.com'], $email->getCc());
        $this->assertSame(['eve@example.com'], $email->getBcc());
    }

    public function testNullTransportCapturesSentEmails(): void
    {
        $transport = new NullTransport();
        $email = (new Email())->subject('Test 1');

        $this->assertCount(0, $transport->getSentEmails());

        $transport->send($email);
        $this->assertCount(1, $transport->getSentEmails());
        $this->assertSame($email, $transport->getSentEmails()[0]);

        $transport->reset();
        $this->assertCount(0, $transport->getSentEmails());
    }

    public function testMailerInjectsDefaultFromWhenMissing(): void
    {
        $transport = new NullTransport();
        $mailer = new Mailer($transport, 'noreply@example.com');

        $email = (new Email())->to('user@example.com')->subject('Notification');
        $mailer->send($email);

        $sent = $transport->getSentEmails();
        $this->assertCount(1, $sent);
        $this->assertSame('noreply@example.com', $sent[0]->getFrom());
    }

    public function testMailerPreservesExplicitFrom(): void
    {
        $transport = new NullTransport();
        $mailer = new Mailer($transport, 'noreply@example.com');

        $email = (new Email())
            ->from('custom@example.com')
            ->to('user@example.com');

        $mailer->send($email);

        $sent = $transport->getSentEmails();
        $this->assertCount(1, $sent);
        $this->assertSame('custom@example.com', $sent[0]->getFrom());
    }

    public function testMailerReturnsTransport(): void
    {
        $transport = new NullTransport();
        $mailer = new Mailer($transport);

        $this->assertSame($transport, $mailer->getTransport());
    }
}
