<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Config\FeatureConfig;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Phase 2 #1 (per-feature config) test.
 *
 * Exercises both the standalone FeatureConfig reader and the
 * Kernel::config() accessor that uses it.
 */
final class FeatureConfigTest extends TestCase
{
    private const PROJECT_DIR = __DIR__ . '/..';

    public function testKernelConfigReturnsHelloFeatureValue(): void
    {
        $kernel = new Kernel(self::PROJECT_DIR);
        self::assertSame(60, $kernel->config('Hello', 'cache_ttl'));
        self::assertSame(100, $kernel->config('Hello', 'rate_limit'));
        self::assertSame(
            ['show_emoji' => true, 'compact_view' => false],
            $kernel->config('Hello', 'feature_flags')
        );
    }

    public function testKernelConfigReturnsDefaultForMissingKey(): void
    {
        $kernel = new Kernel(self::PROJECT_DIR);
        self::assertNull($kernel->config('Hello', 'not_a_key'));
        self::assertSame('fallback', $kernel->config('Hello', 'not_a_key', 'fallback'));
        self::assertSame('fallback', $kernel->config('NoSuchFeature', 'whatever', 'fallback'));
    }

    public function testFeatureConfigReaderHandlesMissingFile(): void
    {
        // Point at an empty dir — no feature dirs, no configs
        $tmp = sys_get_temp_dir() . '/nqphp-cfg-empty-' . uniqid();
        mkdir($tmp, 0755, true);
        try {
            $config = new FeatureConfig([$tmp]);
            self::assertSame([], $config->load()->features());
        } finally {
            rmdir($tmp);
        }
    }

    public function testFeatureConfigReaderSkipsInvalidFile(): void
    {
        $tmp = sys_get_temp_dir() . '/nqphp-cfg-bad-' . uniqid();
        mkdir($tmp . '/BadFeature', 0755, true);
        file_put_contents($tmp . '/BadFeature/config.php', '<?php return "not an array";');
        try {
            $config = new FeatureConfig([$tmp]);
            self::assertSame([], $config->load()->features(),
                'config.php returning a non-array should be silently skipped');
        } finally {
            unlink($tmp . '/BadFeature/config.php');
            rmdir($tmp . '/BadFeature');
            rmdir($tmp);
        }
    }
}
