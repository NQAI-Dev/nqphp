<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\FileDownloadResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileDownloadResponseTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/nqphp_download_test_' . uniqid() . '.txt';
        file_put_contents($this->tempFile, 'Sample content for download');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testDefaultAttachmentDownload(): void
    {
        $response = new FileDownloadResponse($this->tempFile, 'report.txt');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'attachment; filename=report.txt',
            $response->headers->get('Content-Disposition')
        );
        $this->assertSame($this->tempFile, $response->getFile()->getPathname());
    }

    public function testInlineDisposition(): void
    {
        $response = new FileDownloadResponse(
            $this->tempFile,
            'preview.txt',
            ResponseHeaderBag::DISPOSITION_INLINE
        );

        $this->assertSame(
            'inline; filename=preview.txt',
            $response->headers->get('Content-Disposition')
        );
    }

    public function testCustomStatusAndHeaders(): void
    {
        $response = new FileDownloadResponse(
            $this->tempFile,
            'custom.txt',
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            201,
            ['X-Download-Source' => 'Storage']
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Storage', $response->headers->get('X-Download-Source'));
    }
}
