<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\MigrateCommand;
use Nqphp\Core\Console\Command\MigrateRollbackCommand;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCommandsTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_migrate_cmd_test_' . uniqid();
        @mkdir($this->tempDir . '/migrations', 0777, true);
        @mkdir($this->tempDir . '/config', 0777, true);

        // Configure sqlite in-memory db with busy_timeout
        $config = "database:\n  driver: 'sqlite'\n  database: ':memory:'\n";
        file_put_contents($this->tempDir . '/config/database.yaml', $config);
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

    public function testMigrateWhenNothingToMigrate(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new MigrateCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Running database migrations...', $output);
        $this->assertStringContainsString('Nothing to migrate.', $output);
    }

    public function testMigrateAndRollbackFlow(): void
    {
        $migrationFile = $this->tempDir . '/migrations/2026_09_20_000001_create_posts_table.php';
        $migrationContent = <<<'PHP'
<?php

declare(strict_types=1);

use Nqphp\Core\Migration\AbstractMigration;

final class Migration_2026_09_20_000001_create_posts_table extends AbstractMigration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT);");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS posts;");
    }

    public function getDescription(): string
    {
        return 'Create posts table';
    }
}
PHP;
        file_put_contents($migrationFile, $migrationContent);

        $kernel = new Kernel($this->tempDir);
        $pdo = $kernel->pdo();

        // 1. Run MigrateCommand
        $migrateCmd = new MigrateCommand($kernel);
        $migrateTester = new CommandTester($migrateCmd);
        $exitCode = $migrateTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $migrateTester->getDisplay();
        $this->assertStringContainsString('Migrated: 2026_09_20_000001_create_posts_table', $output);
        $this->assertStringContainsString('Database migration completed successfully.', $output);

        // Verify table exists in SQLite
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='posts'");
        $this->assertSame('posts', $stmt->fetchColumn());
        $stmt->closeCursor();

        // 2. Run MigrateRollbackCommand
        $rollbackCmd = new MigrateRollbackCommand($kernel);
        $rollbackTester = new CommandTester($rollbackCmd);
        $rollbackExit = $rollbackTester->execute([]);

        $this->assertSame(Command::SUCCESS, $rollbackExit);
        $rollbackOutput = $rollbackTester->getDisplay();
        $this->assertStringContainsString('Rolled back: 2026_09_20_000001_create_posts_table', $rollbackOutput);
        $this->assertStringContainsString('Rollback completed successfully.', $rollbackOutput);

        // Verify table dropped
        $stmtAfter = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='posts'");
        $this->assertFalse($stmtAfter->fetchColumn());
        $stmtAfter->closeCursor();
    }

    public function testRollbackWhenNothingToRollback(): void
    {
        $kernel = new Kernel($this->tempDir);
        $command = new MigrateRollbackCommand($kernel);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Nothing to rollback.', $tester->getDisplay());
    }
}
