<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Mailer;

use Nqphp\Core\Mailer\ArrayTransport;
use Nqphp\Core\Mailer\Email;
use PHPUnit\Framework\TestCase;

class ArrayTransportTest extends TestCase
{
    public function testSendAndInspectEmails(): void
    {
        $transport = new ArrayTransport();
        $this->assertSame(0, $transport->count());
        $this->assertNull($transport->last());
        $this->assertFalse($transport->hasRecipient('alice@example.com'));

        $email1 = (new Email())
            ->from('bot@example.com')
            ->to('alice@example.com')
            ->subject('Welcome');

        $transport->send($email1);

        $this->assertSame(1, $transport->count());
        $this->assertSame($email1->getSubject(), $transport->last()?->getSubject());
        $this->assertTrue($transport->hasRecipient('alice@example.com'));
        $this->assertFalse($transport->hasRecipient('bob@example.com'));

        $email2 = (new Email())
            ->from('bot@example.com')
            ->to('bob@example.com')
            ->cc('manager@example.com')
            ->subject('Report');

        $transport->send($email2);

        $this->assertSame(2, $transport->count());
        $this->assertSame('Report', $transport->last()?->getSubject());
        $this->assertTrue($transport->hasRecipient('manager@example.com'));

        $transport->flush();
        $this->assertSame(0, $transport->count());
        $this->assertNull($transport->last());
    }
}
