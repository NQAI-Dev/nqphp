<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Http\FileResponse;
use PHPUnit\Framework\TestCase;

class FileResponseTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'nq_test_');
        file_put_contents($this->tempFile, 'Hello file content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
    }

    public function testDownloadResponse(): void
    {
        $response = FileResponse::download($this->tempFile, 'custom.txt');
        $this->assertSame(200, $response->getStatusCode());

        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('custom.txt', $disposition);
    }

    public function testInlineResponse(): void
    {
        $response = FileResponse::inline($this->tempFile, 'preview.txt');
        $this->assertSame(200, $response->getStatusCode());

        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringContainsString('preview.txt', $disposition);
    }

    public function testFileNotFoundThrows404(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('not found');
        FileResponse::download('/tmp/non_existent_file_nq_404.dat');
    }
}
