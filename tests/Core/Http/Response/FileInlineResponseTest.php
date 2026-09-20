<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\FileInlineResponse;
use PHPUnit\Framework\TestCase;

class FileInlineResponseTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/nqphp_inline_test_' . uniqid() . '.pdf';
        file_put_contents($this->tempFile, '%PDF-1.4 sample content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testDefaultInlineDisposition(): void
    {
        $response = new FileInlineResponse($this->tempFile, 'document.pdf');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'inline; filename=document.pdf',
            $response->headers->get('Content-Disposition')
        );
        $this->assertSame($this->tempFile, $response->getFile()->getPathname());
    }

    public function testWithoutFilenameUsesInline(): void
    {
        $response = new FileInlineResponse($this->tempFile);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('inline; filename=', (string) $response->headers->get('Content-Disposition'));
    }

    public function testCustomHeadersAndStatus(): void
    {
        $response = new FileInlineResponse(
            $this->tempFile,
            'preview.png',
            status: 206,
            headers: ['X-Custom-Header' => 'View']
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('View', $response->headers->get('X-Custom-Header'));
        $this->assertSame(
            'inline; filename=preview.png',
            $response->headers->get('Content-Disposition')
        );
    }
}
