<?php

declare(strict_types=1);

namespace Nqphp\Core\Monitoring;

use PDO;

final class DatabaseHealthCheck implements HealthCheckInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getName(): string
    {
        return 'database';
    }

    public function check(): array
    {
        $start = microtime(true);
        try {
            $stmt = $this->pdo->query('SELECT 1');
            if ($stmt === false) {
                return ['status' => 'down', 'message' => 'Query returned false'];
            }
            $stmt->fetch();
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'meta' => [
                    'driver' => $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                    'latency_ms' => $durationMs,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'message' => $e->getMessage(),
            ];
        }
    }
}
