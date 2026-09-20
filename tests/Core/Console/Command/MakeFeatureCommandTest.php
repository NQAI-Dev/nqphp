<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\MakeFeatureCommand;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MakeFeatureCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_make_feature_test_' . uniqid();
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

    public function testExecuteCreatesVerticalFeatureModule(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new MakeFeatureCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'billing',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $featureDir = $this->tempDir . '/src/Feature/Billing';
        $this->assertDirectoryExists($featureDir . '/Controller');
        $this->assertDirectoryExists($featureDir . '/Entity');
        $this->assertDirectoryExists($featureDir . '/Service');
        $this->assertDirectoryExists($featureDir . '/View');

        $ctrlPath = $featureDir . '/Controller/BillingController.php';
        $this->assertFileExists($ctrlPath);
        $ctrlContent = (string) file_get_contents($ctrlPath);
        $this->assertStringContainsString('namespace App\Feature\Billing\Controller;', $ctrlContent);
        $this->assertStringContainsString('final class BillingController extends AbstractController', $ctrlContent);

        $servicePath = $featureDir . '/Service/BillingService.php';
        $this->assertFileExists($servicePath);
        $serviceContent = (string) file_get_contents($servicePath);
        $this->assertStringContainsString('namespace App\Feature\Billing\Service;', $serviceContent);
        $this->assertStringContainsString('final class BillingService', $serviceContent);
    }

    public function testExecuteFailsIfFeatureAlreadyExists(): void
    {
        $featureDir = $this->tempDir . '/src/Feature/Order';
        @mkdir($featureDir, 0777, true);

        $kernel = new Kernel($this->tempDir);
        $command = new MakeFeatureCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'Order',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('already exists', $tester->getDisplay());
    }
}
