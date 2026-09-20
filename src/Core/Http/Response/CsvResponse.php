<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response for CSV data export with proper headers and encoding.
 */
class CsvResponse extends Response
{
    /**
     * @param array<int, array<int|string, mixed>> $rows
     * @param string|null $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param int $status
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        array $rows = [],
        ?string $filename = null,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
        int $status = Response::HTTP_OK,
        array $headers = []
    ) {
        if (!isset($headers['Content-Type']) && !isset($headers['content-type'])) {
            $headers['Content-Type'] = 'text/csv; charset=UTF-8';
        }

        if ($filename !== null && !isset($headers['Content-Disposition']) && !isset($headers['content-disposition'])) {
            $headers['Content-Disposition'] = sprintf('attachment; filename="%s"', addcslashes($filename, '"'));
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            $content = '';
        } else {
            foreach ($rows as $row) {
                fputcsv($stream, (array) $row, $delimiter, $enclosure, $escape);
            }
            rewind($stream);
            $content = stream_get_contents($stream) ?: '';
            fclose($stream);
        }

        parent::__construct($content, $status, $headers);
    }
}
