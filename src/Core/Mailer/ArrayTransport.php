<?php

declare(strict_types=1);

namespace Nqphp\Core\Mailer;

/**
 * In-memory mail transport for testing and assertions.
 */
final class ArrayTransport implements TransportInterface
{
    /** @var list<Email> */
    private array $emails = [];

    public function send(Email $email): void
    {
        $this->emails[] = clone $email;
    }

    /**
     * @return list<Email>
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    public function count(): int
    {
        return count($this->emails);
    }

    public function last(): ?Email
    {
        if (empty($this->emails)) {
            return null;
        }

        return $this->emails[count($this->emails) - 1];
    }

    public function hasRecipient(string $emailAddress): bool
    {
        foreach ($this->emails as $email) {
            if (in_array($emailAddress, $email->getTo(), true)
                || in_array($emailAddress, $email->getCc(), true)
                || in_array($emailAddress, $email->getBcc(), true)
            ) {
                return true;
            }
        }

        return false;
    }

    public function flush(): void
    {
        $this->emails = [];
    }
}
