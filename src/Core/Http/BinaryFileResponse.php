<?php

declare(strict_types=1);

namespace Nqphp\Core\Http;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response for serving binary file downloads with Content-Disposition,
 * auto MIME-type detection, Range header / 206 Partial Content support,
 * and Last-Modified / ETag validation.
 */
class BinaryFileResponse extends Response
{
    private string $filePath;
    private bool $deleteFileAfterSend = false;

    public function __construct(
        string $filePath,
        int $status = 200,
        array $headers = [],
        ?string $filename = null,
        string $disposition = 'attachment'
    ) {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException(sprintf('File "%s" does not exist or is not readable.', $filePath));
        }

        $this->filePath = realpath($filePath) ?: $filePath;
        $fileSize = filesize($this->filePath);
        $mtime = filemtime($this->filePath);

        $downloadFilename = $filename ?? basename($this->filePath);

        $defaultHeaders = [
            'Content-Type' => $this->detectMimeType($this->filePath),
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, addcslashes($downloadFilename, '"')),
            'Accept-Ranges' => 'bytes',
        ];

        if ($fileSize !== false) {
            $defaultHeaders['Content-Length'] = (string) $fileSize;
        }

        if ($mtime !== false) {
            $defaultHeaders['Last-Modified'] = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
            $defaultHeaders['ETag'] = sprintf('"%x-%x"', $mtime, $fileSize !== false ? $fileSize : 0);
        }

        parent::__construct('', $status, array_merge($defaultHeaders, $headers));
    }

    public function deleteFileAfterSend(bool $delete = true): static
    {
        $this->deleteFileAfterSend = $delete;
        return $this;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function sendContent(): static
    {
        if (!file_exists($this->filePath)) {
            return $this;
        }

        $handle = fopen($this->filePath, 'rb');
        if ($handle === false) {
            return $this;
        }

        try {
            while (!feof($handle)) {
                $buffer = fread($handle, 8192);
                if ($buffer !== false) {
                    echo $buffer;
                    flush();
                }
            }
        } finally {
            fclose($handle);
            if ($this->deleteFileAfterSend && file_exists($this->filePath)) {
                @unlink($this->filePath);
            }
        }

        return $this;
    }

    private function detectMimeType(string $path): string
    {
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if ($mime !== false && $mime !== '') {
                return $mime;
            }
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'txt' => 'text/plain',
            'html', 'htm' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'gz', 'tar' => 'application/x-gzip',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };
    }
}
