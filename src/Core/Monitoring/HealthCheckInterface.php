<?php

declare(strict_types=1);

namespace Nqphp\Core\Monitoring;

interface HealthCheckInterface
{
    public function getName(): string;

    /**
     * @return array{status: 'ok'|'degraded'|'down', message?: string, meta?: array<string, mixed>}
     */
    public function check(): array;
}
