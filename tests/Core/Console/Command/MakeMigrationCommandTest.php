<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\MakeMigrationCommand;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MakeMigrationCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_make_migration_test_' . uniqid();
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

    public function testExecuteCreatesMigrationFile(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new MakeMigrationCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'create_users_table',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $migrationsDir = $this->tempDir . '/migrations';
        $this->assertDirectoryExists($migrationsDir);

        $files = glob($migrationsDir . '/*_create_users_table.php');
        $this->assertNotEmpty($files);

        $filePath = $files[0];
        $content = (string) file_get_contents($filePath);
        $this->assertStringContainsString('use Nqphp\Core\Migration\AbstractMigration;', $content);
        $this->assertStringContainsString('final class Migration_', $content);
        $this->assertStringContainsString('public function up(PDO $pdo): void', $content);
        $this->assertStringContainsString('public function down(PDO $pdo): void', $content);
        $this->assertStringContainsString('return \'Migration create_users_table\';', $content);
    }

    public function testExecuteSanitizesSpecialCharactersInName(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new MakeMigrationCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'name' => 'add-index-to@posts!',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $migrationsDir = $this->tempDir . '/migrations';
        $files = glob($migrationsDir . '/*_add_index_to_posts_.php');
        $this->assertNotEmpty($files);
    }
}
