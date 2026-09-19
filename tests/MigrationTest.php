<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Migration\MigrationRepository;
use Nqphp\Core\Migration\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;

final class MigrationTest extends TestCase
{
    private PDO $pdo;
    private string $tempDir;
    private Migrator $migrator;
    private MigrationRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->tempDir = sys_get_temp_dir() . '/nqphp_mig_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->repo = new MigrationRepository($this->pdo, '_test_migrations');
        $this->migrator = new Migrator($this->repo, $this->pdo, $this->tempDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*.php') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);
    }

    public function testMigrateAndRollback(): void
    {
        $migCode = '<?php
use Nqphp\Core\Migration\AbstractMigration;
use PDO;

class Migration_2026_01_create_posts_table extends AbstractMigration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT);");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS posts;");
    }
}';
        file_put_contents($this->tempDir . '/2026_01_create_posts_table.php', $migCode);

        // 1. Migrate
        $ran = $this->migrator->migrate();
        $this->assertCount(1, $ran);
        $this->assertSame('2026_01_create_posts_table', $ran[0]);

        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='posts'");
        $this->assertSame('posts', $stmt->fetchColumn());
        $stmt->closeCursor();

        // 2. Second migrate does nothing
        $this->assertEmpty($this->migrator->migrate());

        // 3. Rollback
        $rolled = $this->migrator->rollback();
        $this->assertCount(1, $rolled);
        $this->assertSame('2026_01_create_posts_table', $rolled[0]);

        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='posts'");
        $this->assertFalse($stmt->fetchColumn());
        $stmt->closeCursor();
    }
}
