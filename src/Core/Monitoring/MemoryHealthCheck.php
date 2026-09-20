<?php

declare(strict_types=1);

namespace Nqphp\Core\Monitoring;

/**
 * Health check inspecting PHP runtime memory usage against system memory_limit.
 */
class MemoryHealthCheck implements HealthCheckInterface
{
    /**
     * @param float $warningThreshold Percentage ratio (0.0 to 1.0) triggering 'degraded' status
     * @param float $criticalThreshold Percentage ratio (0.0 to 1.0) triggering 'down' status
     */
    public function __construct(
        private readonly float $warningThreshold = 0.80,
        private readonly float $criticalThreshold = 0.90
    ) {
    }

    public function getName(): string
    {
        return 'memory';
    }

    public function check(): array
    {
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        $limitStr = trim(ini_get('memory_limit') ?: '-1');

        $limitBytes = $this->parseMemoryLimit($limitStr);

        $meta = [
            'current_bytes' => $currentUsage,
            'peak_bytes' => $peakUsage,
            'limit_string' => $limitStr,
            'limit_bytes' => $limitBytes,
        ];

        if ($limitBytes <= 0) {
            return [
                'status' => 'ok',
                'message' => 'Memory limit is unlimited or unparseable.',
                'meta' => $meta,
            ];
        }

        $ratio = $currentUsage / $limitBytes;
        $meta['usage_ratio'] = round($ratio, 4);

        if ($ratio >= $this->criticalThreshold) {
            return [
                'status' => 'down',
                'message' => sprintf(
                    'Memory usage is critical: %.1f%% used (%s of %s)',
                    $ratio * 100,
                    $this->formatBytes($currentUsage),
                    $limitStr
                ),
                'meta' => $meta,
            ];
        }

        if ($ratio >= $this->warningThreshold) {
            return [
                'status' => 'degraded',
                'message' => sprintf(
                    'Memory usage is elevated: %.1f%% used (%s of %s)',
                    $ratio * 100,
                    $this->formatBytes($currentUsage),
                    $limitStr
                ),
                'meta' => $meta,
            ];
        }

        return [
            'status' => 'ok',
            'message' => sprintf(
                'Memory usage is normal: %.1f%% used (%s of %s)',
                $ratio * 100,
                $this->formatBytes($currentUsage),
                $limitStr
            ),
            'meta' => $meta,
        ];
    }

    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return -1;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $limit,
        };
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $power = min((int) $power, count($units) - 1);
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
