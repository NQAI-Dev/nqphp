<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response representation with application/xml content-type and UTF-8 charset.
 */
class XmlResponse extends Response
{
    /**
     * @param string $xml
     * @param int $status
     * @param array<string, string|string[]> $headers
     */
    public function __construct(string $xml = '', int $status = Response::HTTP_OK, array $headers = [])
    {
        if (!isset($headers['Content-Type']) && !isset($headers['content-type'])) {
            $headers['Content-Type'] = 'application/xml; charset=UTF-8';
        }

        parent::__construct($xml, $status, $headers);
    }
}
