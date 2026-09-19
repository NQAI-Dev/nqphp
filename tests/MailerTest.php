<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Mailer\Email;
use Nqphp\Core\Mailer\Mailer;
use Nqphp\Core\Mailer\NullTransport;
use Nqphp\Core\Queue\DatabaseQueue;
use Nqphp\Core\Queue\SendEmailJob;
use Nqphp\Core\Queue\Worker;
use PDO;
use PHPUnit\Framework\TestCase;

final class MailerTest extends TestCase
{
    private NullTransport $transport;
    private Mailer $mailer;

    protected function setUp(): void
    {
        $this->transport = new NullTransport();
        $this->mailer = new Mailer($this->transport, 'no-reply@nqphp.dev');
    }

    public function testSendEmailDirectly(): void
    {
        $email = (new Email())
            ->to('user@example.com')
            ->subject('Welcome to nqphp')
            ->text('Hello from macro framework!')
            ->html('<h1>Hello from macro framework!</h1>');

        $this->mailer->send($email);

        $sent = $this->transport->getSentEmails();
        $this->assertCount(1, $sent);
        $this->assertSame('no-reply@nqphp.dev', $sent[0]->getFrom());
        $this->assertSame(['user@example.com'], $sent[0]->getTo());
        $this->assertSame('Welcome to nqphp', $sent[0]->getSubject());
        $this->assertSame('Hello from macro framework!', $sent[0]->getTextBody());
        $this->assertSame('<h1>Hello from macro framework!</h1>', $sent[0]->getHtmlBody());
    }

    public function testSendEmailViaQueueJob(): void
    {
        SendEmailJob::setMailer($this->mailer);

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $queue = new DatabaseQueue($pdo, '_test_email_jobs');

        $email = (new Email())
            ->from('custom@nqphp.dev')
            ->to('subscriber@example.com')
            ->subject('Queue Email Test')
            ->text('Async background delivery');

        $job = new SendEmailJob($email);
        $queue->push($job);
        $this->assertSame(1, $queue->count());

        $worker = new Worker($queue);
        $this->assertTrue($worker->processNextJob());

        $sent = $this->transport->getSentEmails();
        $this->assertCount(1, $sent);
        $this->assertSame('custom@nqphp.dev', $sent[0]->getFrom());
        $this->assertSame(['subscriber@example.com'], $sent[0]->getTo());
        $this->assertSame(0, $queue->count());
    }
}
