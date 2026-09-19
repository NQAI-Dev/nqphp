<?php

declare(strict_types=1);

namespace Nqphp\Core\Mailer;

use RuntimeException;

final class SendmailTransport implements TransportInterface
{
    private string $command;

    public function __construct(string $command = '/usr/sbin/sendmail -bs')
    {
        $this->command = $command;
    }

    public function send(Email $email): void
    {
        $to = implode(', ', $email->getTo());
        if ($to === '') {
            throw new RuntimeException('Cannot send email without recipients');
        }

        $headers = $email->getHeaders();
        if ($email->getFrom() !== '') {
            $headers['From'] = $email->getFrom();
        }
        if (!empty($email->getCc())) {
            $headers['Cc'] = implode(', ', $email->getCc());
        }
        if (!empty($email->getBcc())) {
            $headers['Bcc'] = implode(', ', $email->getBcc());
        }

        $body = $email->getHtmlBody() !== '' ? $email->getHtmlBody() : $email->getTextBody();
        if ($email->getHtmlBody() !== '') {
            $headers['MIME-Version'] = '1.0';
            $headers['Content-Type'] = 'text/html; charset=utf-8';
        } else {
            $headers['Content-Type'] = 'text/plain; charset=utf-8';
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }

        $rawHeaders = implode("\r\n", $headerLines);
        $subject = $email->getSubject();

        // Native PHP mail fallback if sendmail binary invoked via mail()
        $success = @mail($to, $subject, $body, $rawHeaders);
        if (!$success && !empty(error_get_last())) {
            throw new RuntimeException('Failed to send email via sendmail transport');
        }
    }
}
