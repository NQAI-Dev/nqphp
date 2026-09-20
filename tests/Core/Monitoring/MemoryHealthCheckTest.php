<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Monitoring;

use Nqphp\Core\Monitoring\MemoryHealthCheck;
use PHPUnit\Framework\TestCase;

class MemoryHealthCheckTest extends TestCase
{
    public function testGetName(): void
    {
        $check = new MemoryHealthCheck();
        $this->assertSame('memory', $check->getName());
    }

    public function testCheckReturnsValidStatusStructure(): void
    {
        $check = new MemoryHealthCheck();
        $result = $check->check();

        $this->assertArrayHasKey('status', $result);
        $this->assertContains($result['status'], ['ok', 'degraded', 'down']);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('current_bytes', $result['meta']);
        $this->assertArrayHasKey('peak_bytes', $result['meta']);
        $this->assertArrayHasKey('limit_string', $result['meta']);
    }

    public function testThresholdTriggersWarningOrDown(): void
    {
        // 0.00000001 threshold will mark almost any real memory usage as critical
        $criticalCheck = new MemoryHealthCheck(0.000000001, 0.000000002);
        $result = $criticalCheck->check();

        // If memory_limit is set (not unlimited -1), it should trigger down
        if ($result['meta']['limit_bytes'] > 0) {
            $this->assertSame('down', $result['status']);
        } else {
            $this->assertSame('ok', $result['status']);
        }
    }
}
