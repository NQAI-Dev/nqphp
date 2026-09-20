<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * HTTP response for initiating binary file downloads with auto Content-Disposition.
 */
class FileDownloadResponse extends BinaryFileResponse
{
    /**
     * @param \SplFileInfo|string $file
     * @param string|null $filename
     * @param string $disposition ResponseHeaderBag::DISPOSITION_ATTACHMENT or DISPOSITION_INLINE
     * @param int $status
     * @param array<string, string|string[]> $headers
     * @param bool $public
     * @param bool $autoEtag
     * @param bool $autoLastModified
     */
    public function __construct(
        \SplFileInfo|string $file,
        ?string $filename = null,
        string $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        int $status = 200,
        array $headers = [],
        bool $public = true,
        bool $autoEtag = false,
        bool $autoLastModified = true
    ) {
        parent::__construct($file, $status, $headers, $public, null, false, $autoLastModified);

        if ($autoEtag) {
            $this->setAutoEtag();
        }

        $this->setContentDisposition($disposition, $filename ?? '');
    }
}
