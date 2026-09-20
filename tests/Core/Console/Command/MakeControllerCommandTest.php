<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\MakeControllerCommand;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MakeControllerCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_make_controller_test_' . uniqid();
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

    public function testExecuteCreatesControllerSuccessfully(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new MakeControllerCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'feature' => 'auth',
            'name' => 'login',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $expectedFile = $this->tempDir . '/src/Feature/Auth/Controller/LoginController.php';
        $this->assertFileExists($expectedFile);

        $content = (string) file_get_contents($expectedFile);
        $this->assertStringContainsString('namespace App\Feature\Auth\Controller;', $content);
        $this->assertStringContainsString('final class LoginController extends AbstractController', $content);
        $this->assertStringContainsString('#[Route(\'/\' . strtolower(\'Auth\')', $content);
    }

    public function testExecuteFailsIfControllerAlreadyExists(): void
    {
        $dir = $this->tempDir . '/src/Feature/Catalog/Controller';
        @mkdir($dir, 0777, true);
        $targetFile = $dir . '/ProductController.php';
        file_put_contents($targetFile, '<?php // existing');

        $kernel = new Kernel($this->tempDir);
        $command = new MakeControllerCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'feature' => 'Catalog',
            'name' => 'ProductController',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('already exists', $tester->getDisplay());
    }
}
