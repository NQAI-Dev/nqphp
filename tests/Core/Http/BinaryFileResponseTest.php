<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http;

use InvalidArgumentException;
use Nqphp\Core\Http\BinaryFileResponse;
use PHPUnit\Framework\TestCase;

class BinaryFileResponseTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/nqphp_binary_test_' . uniqid() . '.txt';
        file_put_contents($this->tempFile, 'Hello Binary Stream World!');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
    }

    public function testConstructSetsHeadersAndMetadata(): void
    {
        $response = new BinaryFileResponse($this->tempFile, 200, [], 'custom_download.txt');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(realpath($this->tempFile), $response->getFilePath());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename="custom_download.txt"', $response->headers->get('Content-Disposition'));
        $this->assertSame((string) strlen('Hello Binary Stream World!'), $response->headers->get('Content-Length'));
        $this->assertSame('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertTrue($response->headers->has('Last-Modified'));
        $this->assertTrue($response->headers->has('ETag'));
    }

    public function testThrowsExceptionIfFileNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BinaryFileResponse('/non/existent/path/for/sure/12345.bin');
    }

    public function testSendContentStreamsFile(): void
    {
        $response = new BinaryFileResponse($this->tempFile);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame('Hello Binary Stream World!', $output);
        $this->assertFileExists($this->tempFile);
    }

    public function testDeleteFileAfterSendRemovesFile(): void
    {
        $response = new BinaryFileResponse($this->tempFile);
        $response->deleteFileAfterSend(true);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame('Hello Binary Stream World!', $output);
        $this->assertFileDoesNotExist($this->tempFile);
    }
}
