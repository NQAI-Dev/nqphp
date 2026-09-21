<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response representing newline-delimited JSON (application/x-ndjson).
 */
class NdjsonResponse extends Response
{
    /**
     * @param iterable<mixed> $rows
     * @param int $status
     * @param array<string, string|list<string>> $headers
     * @param int $encodingOptions
     */
    public function __construct(
        iterable $rows,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
        $lines = [];
        foreach ($rows as $row) {
            $json = json_encode($row, $encodingOptions);
            if ($json === false) {
                throw new InvalidArgumentException(sprintf('Failed to encode NDJSON item: %s', json_last_error_msg()));
            }
            $lines[] = $json;
        }

        $content = empty($lines) ? '' : implode("\n", $lines) . "\n";

        $headers['Content-Type'] = 'application/x-ndjson; charset=UTF-8';

        parent::__construct($content, $status, $headers);
    }
}
