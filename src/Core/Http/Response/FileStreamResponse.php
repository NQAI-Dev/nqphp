<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streamed HTTP response for sending chunked file or resource streams to clients.
 */
class FileStreamResponse extends StreamedResponse
{
    /**
     * @param resource $stream Open readable stream resource
     * @param int $status HTTP status code
     * @param array<string, string|string[]> $headers
     * @param int $chunkSize Buffer chunk size in bytes (default 64KB)
     */
    public function __construct(
        mixed $stream,
        int $status = 200,
        array $headers = [],
        int $chunkSize = 65536
    ) {
        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new \InvalidArgumentException('Аргумент $stream должен быть корректным ресурсом потока.');
        }

        $callback = static function () use ($stream, $chunkSize): void {
            while (!feof($stream)) {
                $buffer = fread($stream, $chunkSize);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                flush();
            }
            if (is_resource($stream)) {
                fclose($stream);
            }
        };

        parent::__construct($callback, $status, $headers);
    }
}
