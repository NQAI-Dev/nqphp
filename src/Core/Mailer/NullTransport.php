<?php

declare(strict_types=1);

namespace Nqphp\Core\Mailer;

final class NullTransport implements TransportInterface
{
    /** @var list<Email> */
    private array $sentEmails = [];

    public function send(Email $email): void
    {
        $this->sentEmails[] = $email;
    }

    /**
     * @return list<Email>
     */
    public function getSentEmails(): array
    {
        return $this->sentEmails;
    }

    public function reset(): void
    {
        $this->sentEmails = [];
    }
}
