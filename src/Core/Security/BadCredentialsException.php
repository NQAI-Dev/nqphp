<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

use RuntimeException;

class BadCredentialsException extends RuntimeException
{
    public function __construct(string $message = 'Invalid credentials.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
