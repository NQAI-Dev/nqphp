<?php

declare(strict_types=1);

namespace Nqphp\Core\Mailer;

final class Mailer
{
    private TransportInterface $transport;
    private ?string $defaultFrom;

    public function __construct(TransportInterface $transport, ?string $defaultFrom = null)
    {
        $this->transport = $transport;
        $this->defaultFrom = $defaultFrom;
    }

    public function send(Email $email): void
    {
        if ($email->getFrom() === '' && $this->defaultFrom !== null) {
            $email->from($this->defaultFrom);
        }

        $this->transport->send($email);
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }
}
