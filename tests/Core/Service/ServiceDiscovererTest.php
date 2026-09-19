<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Service;

use Nqphp\Core\Attribute\Service as ServiceAttr;
use Nqphp\Core\Service\ServiceDiscoverer;
use PHPUnit\Framework\TestCase;

class ServiceDiscovererTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_service_test_' . uniqid();
        @mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        @rmdir($this->tempDir);
    }

    public function testDiscoverServices(): void
    {
        $file1 = $this->tempDir . '/CustomService.php';
        file_put_contents($file1, <<<'PHP'
<?php

namespace Nqphp\Tests\Core\Service\Fixtures;

use Nqphp\Core\Attribute\Service;

#[Service(name: 'custom_service', scope: 'singleton', tags: ['app', 'core'])]
class CustomService
{
}
PHP
        );

        $file2 = $this->tempDir . '/PlainClass.php';
        file_put_contents($file2, <<<'PHP'
<?php

namespace Nqphp\Tests\Core\Service\Fixtures;

class PlainClass
{
}
PHP
        );

        $discoverer = new ServiceDiscoverer([$this->tempDir]);
        $discoverer->discover();

        $this->assertTrue($discoverer->has('custom_service'));
        $this->assertFalse($discoverer->has('plain_class'));

        $desc = $discoverer->describe('custom_service');
        $this->assertNotNull($desc);
        $this->assertSame('Nqphp\Tests\Core\Service\Fixtures\CustomService', $desc['class']);
        $this->assertSame('singleton', $desc['scope']);
        $this->assertSame(['app', 'core'], $desc['tags']);

        $all = $discoverer->all();
        $this->assertArrayHasKey('custom_service', $all);
    }

    public function testHandlesNonExistentDirectory(): void
    {
        $discoverer = new ServiceDiscoverer(['/path/to/nonexistent/dir_' . uniqid()]);
        $discoverer->discover();

        $this->assertSame([], $discoverer->all());
    }
}
