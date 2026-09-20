<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response representation with text/html content-type and UTF-8 charset.
 */
class HtmlResponse extends Response
{
    /**
     * @param string $html
     * @param int $status
     * @param array<string, string|string[]> $headers
     */
    public function __construct(string $html = '', int $status = Response::HTTP_OK, array $headers = [])
    {
        if (!isset($headers['Content-Type']) && !isset($headers['content-type'])) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        }

        parent::__construct($html, $status, $headers);
    }
}
