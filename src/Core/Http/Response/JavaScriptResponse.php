<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response for serving JavaScript content with proper MIME type and charset headers.
 */
class JavaScriptResponse extends Response
{
    /**
     * @param string $content JavaScript source code
     * @param int $status HTTP status code (defaults to 200)
     * @param array<string, string|string[]> $headers Additional HTTP headers
     */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $defaultHeaders = [
            'Content-Type' => 'application/javascript; charset=UTF-8',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        parent::__construct($content, $status, $headers);
    }
}
