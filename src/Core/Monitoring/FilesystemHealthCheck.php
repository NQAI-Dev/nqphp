<?php

declare(strict_types=1);

namespace Nqphp\Core\Monitoring;

use Nqphp\Core\Filesystem\StorageInterface;
use Throwable;

/**
 * Health check that verifies storage availability by performing write, read, and delete operations.
 */
class FilesystemHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly string $name = 'filesystem',
        private readonly string $checkFile = '.healthcheck_probe'
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function check(): array
    {
        $start = microtime(true);
        $probeContent = 'probe_' . bin2hex(random_bytes(8));

        try {
            $this->storage->write($this->checkFile, $probeContent);

            if (!$this->storage->has($this->checkFile)) {
                $latencyMs = round((microtime(true) - $start) * 1000, 2);
                return [
                    'status' => 'down',
                    'message' => 'Filesystem probe file was written but cannot be detected',
                    'meta' => ['latency_ms' => $latencyMs],
                ];
            }

            $read = $this->storage->read($this->checkFile);
            if ($read !== $probeContent) {
                $latencyMs = round((microtime(true) - $start) * 1000, 2);
                return [
                    'status' => 'down',
                    'message' => 'Filesystem probe content mismatch',
                    'meta' => ['latency_ms' => $latencyMs],
                ];
            }

            $this->storage->delete($this->checkFile);
            $latencyMs = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'meta' => ['latency_ms' => $latencyMs],
            ];
        } catch (Throwable $e) {
            $latencyMs = round((microtime(true) - $start) * 1000, 2);
            return [
                'status' => 'down',
                'message' => $e->getMessage(),
                'meta' => ['latency_ms' => $latencyMs],
            ];
        }
    }
}
