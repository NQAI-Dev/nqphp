<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation;

use Nqphp\Core\Exception\HttpException;

/**
 * Thrown when request input fails validation.
 *
 * Carries the per-field error map produced by the Validator so the
 * ErrorResponseFormatter can surface machine-readable details in the
 * 422 problem+json response:
 *
 *   {
 *     "title": "Unprocessable Content",
 *     "status": 422,
 *     "detail": "Validation failed",
 *     "errors": {"email": ["Invalid email format"], ...}
 *   }
 */
final class ValidationException extends HttpException
{
    /** @var array<string, list<string>> */
    private readonly array $errors;

    /** @param array<string, list<string>> $errors field name → list of messages */
    public function __construct(
        array $errors,
        string $message = 'Validation failed',
    ) {
        parent::__construct(422, $message);
        $this->errors = $errors;
    }

    /** @return array<string, list<string>> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
