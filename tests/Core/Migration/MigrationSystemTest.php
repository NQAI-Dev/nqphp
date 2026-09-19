<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Migration;

use Nqphp\Core\Migration\AbstractMigration;
use Nqphp\Core\Migration\MigrationRepository;
use Nqphp\Core\Migration\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;

class MigrationSystemTest extends TestCase
{
    private PDO $pdo;
    private string $migrationsDir;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->migrationsDir = sys_get_temp_dir() . '/nqphp_migration_tests_' . uniqid();
        @mkdir($this->migrationsDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->migrationsDir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        @rmdir($this->migrationsDir);
    }

    public function testRepositoryEnsuresTableAndTracksBatches(): void
    {
        $repo = new MigrationRepository($this->pdo, 'test_migrations');

        $this->assertSame([], $repo->getExecutedMigrations());
        $this->assertSame(1, $repo->getNextBatchNumber());

        $repo->record('2026_09_19_000001_create_users_table', 1);
        $repo->record('2026_09_19_000002_create_posts_table', 1);

        $this->assertSame([
            '2026_09_19_000001_create_users_table',
            '2026_09_19_000002_create_posts_table',
        ], $repo->getExecutedMigrations());

        $this->assertSame(2, $repo->getNextBatchNumber());

        $lastBatch = $repo->getLastBatchMigrations();
        $this->assertCount(2, $lastBatch);

        $repo->delete('2026_09_19_000002_create_posts_table');
        $this->assertSame(['2026_09_19_000001_create_users_table'], $repo->getExecutedMigrations());
    }

    public function testMigratorRunsPendingMigrationsAndRollbacks(): void
    {
        $this->createMigrationFile(
            '2026_09_19_000001_create_test_table',
            'CreateTestTable',
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)',
            'DROP TABLE test_table'
        );

        $repo = new MigrationRepository($this->pdo, '_nq_migrations');
        $migrator = new Migrator($repo, $this->pdo, $this->migrationsDir);

        $ran = $migrator->migrate();
        $this->assertSame(['2026_09_19_000001_create_test_table'], $ran);

        // Check table exists in sqlite
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'");
        $this->assertSame('test_table', $stmt->fetchColumn());
        $stmt->closeCursor();
        unset($stmt);

        // Second run should have no pending
        $ranAgain = $migrator->migrate();
        $this->assertSame([], $ranAgain);

        // Rollback
        $rolledBack = $migrator->rollback();
        $this->assertSame(['2026_09_19_000001_create_test_table'], $rolledBack);

        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'");
        $this->assertFalse($stmt->fetchColumn());
        $stmt->closeCursor();
        unset($stmt);
        $this->assertSame([], $repo->getExecutedMigrations());
    }

    private function createMigrationFile(string $filename, string $className, string $upSql, string $downSql): void
    {
        $code = <<<PHP
<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Migration\Fixtures;

use Nqphp\Core\Migration\AbstractMigration;
use PDO;

class {$className} extends AbstractMigration
{
    public function up(PDO \$pdo): void
    {
        \$this->execute(\$pdo, '{$upSql}');
    }

    public function down(PDO \$pdo): void
    {
        \$this->execute(\$pdo, '{$downSql}');
    }
}
PHP;
        file_put_contents($this->migrationsDir . '/' . $filename . '.php', $code);
    }
}
