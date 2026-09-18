<?php

declare(strict_types=1);

namespace Nqphp\Core\Exception;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    private int $statusCode;

    public function __construct(int $statusCode, string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
