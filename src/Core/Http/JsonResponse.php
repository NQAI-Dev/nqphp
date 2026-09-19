<?php

declare(strict_types=1);

namespace Nqphp\Core\Http;

use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;

/**
 * Enhanced JSON response with standard envelope helpers.
 */
class JsonResponse extends SymfonyJsonResponse
{
    /**
     * Create a standard success JSON response.
     *
     * @param mixed $data Payload
     * @param string|null $message Optional status message
     * @param int $status HTTP status code (default 200)
     * @param array<string, string> $headers Additional headers
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $headers = []
    ): self {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return new self($payload, $status, $headers);
    }

    /**
     * Create a standard error JSON response.
     *
     * @param string $message Error message
     * @param int $status HTTP error status code (default 400)
     * @param mixed $errors Detailed validation or contextual error items
     * @param array<string, string> $headers Additional headers
     */
    public static function error(
        string $message,
        int $status = 400,
        mixed $errors = null,
        array $headers = []
    ): self {
        $payload = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status,
            ],
        ];

        if ($errors !== null) {
            $payload['error']['details'] = $errors;
        }

        return new self($payload, $status, $headers);
    }
}
