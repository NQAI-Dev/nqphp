<?php

declare(strict_types=1);

namespace Nqphp\Core\Mailer;

interface TransportInterface
{
    /**
     * Send an email through the transport.
     */
    public function send(Email $email): void;
}
