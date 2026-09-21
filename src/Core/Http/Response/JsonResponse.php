<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response for serving JSON payloads with automated serialization and charset headers.
 */
class JsonResponse extends Response
{
    /**
     * @param mixed $data Data to be JSON-encoded
     * @param int $status HTTP status code (defaults to 200)
     * @param array<string, string|string[]> $headers Additional HTTP headers
     * @param int $encodingOptions JSON encode flags
     * @throws JsonException
     */
    public function __construct(
        mixed $data = null,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) {
        $content = $data !== null ? json_encode($data, $encodingOptions) : '{}';

        $defaultHeaders = [
            'Content-Type' => 'application/json; charset=UTF-8',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        parent::__construct($content, $status, $headers);
    }
}
