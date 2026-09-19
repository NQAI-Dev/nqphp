<?php

declare(strict_types=1);

namespace Nqphp\Core\Http;

use Nqphp\Core\Exception\HttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Convenience helper for serving file downloads and inline media
 * with automatic mime detection, content disposition, and etag cache headers.
 */
class FileResponse extends BinaryFileResponse
{
    /**
     * Create a file download response (Content-Disposition: attachment).
     */
    public static function download(
        string $filePath,
        ?string $fileName = null,
        array $headers = [],
        bool $public = true
    ): self {
        if (!file_exists($filePath)) {
            throw new HttpException(404, sprintf('File "%s" not found.', basename($filePath)));
        }

        $response = new self($filePath, 200, $headers, $public);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName ?? basename($filePath)
        );
        $response->setAutoEtag();

        return $response;
    }

    /**
     * Create an inline file response (Content-Disposition: inline, e.g. for PDFs/images).
     */
    public static function inline(
        string $filePath,
        ?string $fileName = null,
        array $headers = [],
        bool $public = true
    ): self {
        if (!file_exists($filePath)) {
            throw new HttpException(404, sprintf('File "%s" not found.', basename($filePath)));
        }

        $response = new self($filePath, 200, $headers, $public);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $fileName ?? basename($filePath)
        );
        $response->setAutoEtag();

        return $response;
    }
}
