<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http;

use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Http\FileResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileResponseTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'nqphp_fileresponse_test_');
        file_put_contents($this->tempFile, 'Hello world file content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
    }

    public function testDownloadCreatesAttachmentDisposition(): void
    {
        $response = FileResponse::download($this->tempFile, 'report.txt');

        $this->assertSame(200, $response->getStatusCode());
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('report.txt', $disposition);
        $this->assertNotNull($response->getEtag());
    }

    public function testDownloadDefaultsToBasename(): void
    {
        $response = FileResponse::download($this->tempFile);
        $disposition = (string) $response->headers->get('Content-Disposition');

        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString(basename($this->tempFile), $disposition);
    }

    public function testInlineCreatesInlineDisposition(): void
    {
        $response = FileResponse::inline($this->tempFile, 'preview.txt');

        $this->assertSame(200, $response->getStatusCode());
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringContainsString('preview.txt', $disposition);
        $this->assertNotNull($response->getEtag());
    }

    public function testDownloadThrows404OnMissingFile(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        FileResponse::download('/path/to/nonexistent/file_' . uniqid() . '.txt');
    }

    public function testInlineThrows404OnMissingFile(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        FileResponse::inline('/path/to/nonexistent/file_' . uniqid() . '.txt');
    }
}
