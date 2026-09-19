<?php

declare(strict_types=1);

namespace Nqphp\Core\Monitoring;

final class HealthCheckRegistry
{
    /**
     * @var list<HealthCheckInterface>
     */
    private array $checks = [];

    public function register(HealthCheckInterface $check): self
    {
        $this->checks[] = $check;
        return $this;
    }

    /**
     * @return array{
     *   status: 'ok'|'degraded'|'down',
     *   timestamp: string,
     *   checks: array<string, array{status: string, message?: string, meta?: array<string, mixed>}>
     * }
     */
    public function runAll(): array
    {
        $results = [];
        $overallStatus = 'ok';

        foreach ($this->checks as $check) {
            $name = $check->getName();
            $res = $check->check();
            $results[$name] = $res;

            if ($res['status'] === 'down') {
                $overallStatus = 'down';
            } elseif ($res['status'] === 'degraded' && $overallStatus !== 'down') {
                $overallStatus = 'degraded';
            }
        }

        return [
            'status' => $overallStatus,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'checks' => $results,
        ];
    }
}
