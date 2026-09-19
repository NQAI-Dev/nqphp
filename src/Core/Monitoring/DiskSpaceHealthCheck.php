<?php

declare(strict_types=1);

namespace Nqphp\Core\Monitoring;

final class DiskSpaceHealthCheck implements HealthCheckInterface
{
    private string $path;
    private float $warningThresholdPercent;
    private float $criticalThresholdPercent;

    public function __construct(string $path = '/', float $warningThresholdPercent = 85.0, float $criticalThresholdPercent = 95.0)
    {
        $this->path = $path;
        $this->warningThresholdPercent = $warningThresholdPercent;
        $this->criticalThresholdPercent = $criticalThresholdPercent;
    }

    public function getName(): string
    {
        return 'disk_space';
    }

    public function check(): array
    {
        $total = @disk_total_space($this->path);
        $free = @disk_free_space($this->path);

        if ($total === false || $free === false || $total <= 0) {
            return [
                'status' => 'degraded',
                'message' => "Unable to read disk stats for [{$this->path}]",
            ];
        }

        $used = $total - $free;
        $usedPercent = round(($used / $total) * 100, 2);

        $status = 'ok';
        $msg = null;

        if ($usedPercent >= $this->criticalThresholdPercent) {
            $status = 'down';
            $msg = "Disk usage critically high ({$usedPercent}%)";
        } elseif ($usedPercent >= $this->warningThresholdPercent) {
            $status = 'degraded';
            $msg = "Disk usage warning ({$usedPercent}%)";
        }

        $res = [
            'status' => $status,
            'meta' => [
                'path' => $this->path,
                'total_bytes' => $total,
                'free_bytes' => $free,
                'used_percent' => $usedPercent,
            ],
        ];

        if ($msg !== null) {
            $res['message'] = $msg;
        }

        return $res;
    }
}
