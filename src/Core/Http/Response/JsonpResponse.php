<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response for serving JSONP (JSON with Padding) payloads safely.
 */
class JsonpResponse extends Response
{
    /**
     * @param string $callback JavaScript callback identifier
     * @param mixed $data Data to be JSON-encoded
     * @param int $status HTTP status code (defaults to 200)
     * @param array<string, string|string[]> $headers Additional HTTP headers
     * @param int $encodingOptions JSON encode flags
     * @throws InvalidArgumentException|JsonException
     */
    public function __construct(
        string $callback,
        mixed $data = null,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) {
        $this->validateCallback($callback);

        $json = $data !== null ? json_encode($data, $encodingOptions) : '{}';
        $content = "/**/{$callback}({$json});";

        $defaultHeaders = [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        parent::__construct($content, $status, $headers);
    }

    private function validateCallback(string $callback): void
    {
        // Allow valid JS identifier chains like callback, app.cb, _init123
        $parts = explode('.', $callback);
        foreach ($parts as $part) {
            if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $part)) {
                throw new InvalidArgumentException("Недопустимое имя callback для JSONP: '{$callback}'.");
            }
        }
    }
}
