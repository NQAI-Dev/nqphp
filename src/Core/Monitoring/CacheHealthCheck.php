<?php

declare(strict_types=1);

namespace Nqphp\Core\Monitoring;

use Nqphp\Core\Cache\CacheInterface;
use Throwable;

/**
 * Health check verifying read/write roundtrip to the configured cache pool.
 */
class CacheHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly string $name = 'cache',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function check(): array
    {
        $testKey = 'health_check_' . bin2hex(random_bytes(4));
        $testVal = 'ok_' . time();

        try {
            $this->cache->set($testKey, $testVal, 10);
            $retrieved = $this->cache->get($testKey);
            $this->cache->delete($testKey);

            if ($retrieved !== $testVal) {
                return [
                    'status' => 'degraded',
                    'message' => 'Cache write succeeded but retrieved value did not match.',
                ];
            }

            return [
                'status' => 'ok',
                'meta' => [
                    'driver' => (new \ReflectionClass($this->cache))->getShortName(),
                ],
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'down',
                'message' => 'Cache check failed: ' . $e->getMessage(),
            ];
        }
    }
}
