<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\MakeEntityCommand;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MakeEntityCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_make_entity_test_' . uniqid();
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

    public function testExecuteCreatesEntitySuccessfully(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new MakeEntityCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'feature' => 'catalog',
            'name' => 'ProductItem',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $expectedFile = $this->tempDir . '/src/Feature/Catalog/Entity/ProductItem.php';
        $this->assertFileExists($expectedFile);

        $content = (string) file_get_contents($expectedFile);
        $this->assertStringContainsString('namespace App\Feature\Catalog\Entity;', $content);
        $this->assertStringContainsString('#[Entity(table: \'product_items\')]', $content);
        $this->assertStringContainsString('final class ProductItem', $content);
        $this->assertStringContainsString('#[Id]', $content);
    }

    public function testExecuteFailsIfEntityAlreadyExists(): void
    {
        $dir = $this->tempDir . '/src/Feature/Blog/Entity';
        @mkdir($dir, 0777, true);
        $targetFile = $dir . '/Post.php';
        file_put_contents($targetFile, '<?php // existing');

        $kernel = new Kernel($this->tempDir);
        $command = new MakeEntityCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'feature' => 'Blog',
            'name' => 'Post',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('already exists', $tester->getDisplay());
    }
}
