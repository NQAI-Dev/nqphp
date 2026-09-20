<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Container;

use Nqphp\Core\Container\FeatureContainer;
use PHPUnit\Framework\TestCase;

class FeatureContainerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_feature_container_' . uniqid();
        @mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testBuildWithNoServicesYaml(): void
    {
        $featureDir = $this->tempDir . '/Auth';
        @mkdir($featureDir, 0777, true);

        $builder = new FeatureContainer([$this->tempDir]);
        $container = $builder->build();

        $this->assertFalse($container->has('custom_service'));
    }

    public function testBuildLoadsServicesYaml(): void
    {
        $featureDir = $this->tempDir . '/Billing/config';
        @mkdir($featureDir, 0777, true);

        $yaml = <<<YAML
services:
  billing.dummy_service:
    class: stdClass
    public: true
YAML;
        file_put_contents($featureDir . '/services.yaml', $yaml);

        $builder = new FeatureContainer([$this->tempDir]);
        $container = $builder->build();
        $container->compile();

        $this->assertTrue($container->has('billing.dummy_service'));
        $service = $container->get('billing.dummy_service');
        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function testIgnoresNonExistentFeatureRoots(): void
    {
        $builder = new FeatureContainer(['/non/existent/path_' . uniqid()]);
        $container = $builder->build();

        $this->assertFalse($container->isCompiled());
        $container->compile();
        $this->assertTrue($container->isCompiled());
    }
}
