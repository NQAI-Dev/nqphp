<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response representation with text/plain content-type and UTF-8 charset.
 */
class PlainTextResponse extends Response
{
    /**
     * @param string $text
     * @param int $status
     * @param array<string, string|string[]> $headers
     */
    public function __construct(string $text = '', int $status = Response::HTTP_OK, array $headers = [])
    {
        if (!isset($headers['Content-Type']) && !isset($headers['content-type'])) {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }

        parent::__construct($text, $status, $headers);
    }
}
