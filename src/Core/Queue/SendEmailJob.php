<?php

declare(strict_types=1);

namespace Nqphp\Core\Queue;

use Nqphp\Core\Mailer\Email;
use Nqphp\Core\Mailer\Mailer;

final class SendEmailJob extends AbstractJob
{
    private Email $email;
    private static ?Mailer $mailerInstance = null;

    public function __construct(Email $email, int $maxTries = 3, int $retryDelay = 60)
    {
        $this->email = $email;
        $this->maxTries = $maxTries;
        $this->retryDelay = $retryDelay;
    }

    public static function setMailer(Mailer $mailer): void
    {
        self::$mailerInstance = $mailer;
    }

    public function handle(): void
    {
        if (self::$mailerInstance === null) {
            throw new \RuntimeException('Mailer instance not configured for SendEmailJob');
        }

        self::$mailerInstance->send($this->email);
    }

    public function getEmail(): Email
    {
        return $this->email;
    }
}
