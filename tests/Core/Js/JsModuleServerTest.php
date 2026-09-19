<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Js;

use Nqphp\Core\Js\JsModuleServer;
use PHPUnit\Framework\TestCase;

class JsModuleServerTest extends TestCase
{
    private string $frameworkDir;
    private string $featureDir;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . '/nqphp_js_' . uniqid();
        $this->frameworkDir = $base . '/framework';
        $this->featureDir = $base . '/features';

        @mkdir($this->frameworkDir, 0777, true);
        @mkdir($this->featureDir . '/Blog/Resources', 0777, true);

        file_put_contents($this->frameworkDir . '/nqphp-runtime.js', 'console.log("runtime");');
        file_put_contents($this->featureDir . '/Blog/Resources/app.js', 'console.log("blog");');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(dirname($this->frameworkDir));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = scandir($dir);
        if ($files === false) {
            return;
        }
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testServeFrameworkModule(): void
    {
        $server = new JsModuleServer([$this->frameworkDir], [$this->featureDir]);
        $response = $server->serve('nqphp-runtime.js');

        $this->assertNotNull($response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('console.log("runtime");', $response->getContent());
        $this->assertStringContainsString('application/javascript', (string) $response->headers->get('content-type'));
    }

    public function testServeFeatureModule(): void
    {
        $server = new JsModuleServer([$this->frameworkDir], [$this->featureDir]);
        $response = $server->serve('Blog/app.js');

        $this->assertNotNull($response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('console.log("blog");', $response->getContent());
    }

    public function testServeRejectsPathTraversal(): void
    {
        $server = new JsModuleServer([$this->frameworkDir], [$this->featureDir]);

        $this->assertNull($server->serve('../etc/passwd'));
        $this->assertNull($server->serve('Blog/../../secret.js'));
    }

    public function testServeReturnsNullForMissingModule(): void
    {
        $server = new JsModuleServer([$this->frameworkDir], [$this->featureDir]);

        $this->assertNull($server->serve('missing.js'));
        $this->assertNull($server->serve('Blog/nonexistent.js'));
    }

    public function testDiscoverListsModules(): void
    {
        $server = new JsModuleServer([$this->frameworkDir], [$this->featureDir]);
        $modules = $server->discover();

        $this->assertArrayHasKey('/_nqphp/js/nqphp-runtime.js', $modules);
        $this->assertArrayHasKey('/_nqphp/js/Blog/app.js', $modules);
    }
}
