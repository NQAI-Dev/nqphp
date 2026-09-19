<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Monitoring\DatabaseHealthCheck;
use Nqphp\Core\Monitoring\DiskSpaceHealthCheck;
use Nqphp\Core\Monitoring\HealthCheckAction;
use Nqphp\Core\Monitoring\HealthCheckRegistry;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class HealthCheckTest extends TestCase
{
    public function testDatabaseHealthCheckSuccess(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $check = new DatabaseHealthCheck($pdo);

        $this->assertSame('database', $check->getName());
        $res = $check->check();

        $this->assertSame('ok', $res['status']);
        $this->assertArrayHasKey('meta', $res);
        $this->assertSame('sqlite', $res['meta']['driver']);
    }

    public function testDiskSpaceHealthCheck(): void
    {
        $check = new DiskSpaceHealthCheck(__DIR__, 99.0, 99.9);
        $res = $check->check();

        $this->assertContains($res['status'], ['ok', 'degraded']);
        $this->assertArrayHasKey('used_percent', $res['meta']);
    }

    public function testRegistryAggregatesStatusAndActionReturnsJsonResponse(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $registry = new HealthCheckRegistry();
        $registry->register(new DatabaseHealthCheck($pdo));
        $registry->register(new DiskSpaceHealthCheck(__DIR__, 99.9, 100.0));

        $action = new HealthCheckAction($registry);
        $response = $action();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertSame('ok', $data['status']);
        $this->assertArrayHasKey('checks', $data);
        $this->assertArrayHasKey('database', $data['checks']);
        $this->assertArrayHasKey('disk_space', $data['checks']);
    }
}
