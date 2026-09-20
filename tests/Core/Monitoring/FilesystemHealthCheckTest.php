<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Monitoring;

use Nqphp\Core\Filesystem\InMemoryStorage;
use Nqphp\Core\Filesystem\ReadOnlyStorage;
use Nqphp\Core\Monitoring\FilesystemHealthCheck;
use Nqphp\Core\Monitoring\HealthCheckInterface;
use PHPUnit\Framework\TestCase;

class FilesystemHealthCheckTest extends TestCase
{
    public function testImplementsHealthCheckInterface(): void
    {
        $storage = new InMemoryStorage();
        $check = new FilesystemHealthCheck($storage);

        $this->assertInstanceOf(HealthCheckInterface::class, $check);
        $this->assertSame('filesystem', $check->getName());
    }

    public function testHealthyFilesystem(): void
    {
        $storage = new InMemoryStorage();
        $check = new FilesystemHealthCheck($storage);

        $result = $check->check();

        $this->assertSame('ok', $result['status']);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('latency_ms', $result['meta']);
        $this->assertFalse($storage->has('.healthcheck_probe'));
    }

    public function testUnhealthyWhenStorageIsReadOnly(): void
    {
        $storage = new ReadOnlyStorage(new InMemoryStorage());
        $check = new FilesystemHealthCheck($storage);

        $result = $check->check();

        $this->assertSame('down', $result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('storage is read-only', $result['message']);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('latency_ms', $result['meta']);
    }
}
