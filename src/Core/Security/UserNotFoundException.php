<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

use RuntimeException;

class UserNotFoundException extends RuntimeException
{
    public function __construct(string $identifier = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = $identifier !== ''
            ? sprintf('User with identifier "%s" was not found.', $identifier)
            : 'User not found.';
        parent::__construct($message, $code, $previous);
    }
}
