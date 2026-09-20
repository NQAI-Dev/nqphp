<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * HTTP response for displaying binary files inline in the browser (e.g. PDF, images).
 */
class FileInlineResponse extends BinaryFileResponse
{
    /**
     * @param \SplFileInfo|string $file
     * @param string|null $filename
     * @param int $status
     * @param array<string, string|string[]> $headers
     * @param bool $public
     * @param bool $autoEtag
     * @param bool $autoLastModified
     */
    public function __construct(
        \SplFileInfo|string $file,
        ?string $filename = null,
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

        $this->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename ?? '');
    }
}
