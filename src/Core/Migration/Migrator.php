<?php

declare(strict_types=1);

namespace Nqphp\Core\Migration;

use PDO;
use RuntimeException;

final class Migrator
{
    private MigrationRepository $repository;
    private PDO $pdo;
    private string $migrationsDir;

    public function __construct(MigrationRepository $repository, PDO $pdo, string $migrationsDir)
    {
        $this->repository = $repository;
        $this->pdo = $pdo;
        $this->migrationsDir = rtrim($migrationsDir, '/\\');
    }

    /**
     * Run all pending migrations.
     *
     * @return list<string> Executed migration names
     */
    public function migrate(): array
    {
        $files = $this->getMigrationFiles();
        $executed = $this->repository->getExecutedMigrations();
        $pending = array_values(array_diff($files, $executed));

        if (empty($pending)) {
            return [];
        }

        $batch = $this->repository->getNextBatchNumber();
        $ran = [];

        foreach ($pending as $name) {
            $migration = $this->resolveMigration($name);
            $this->pdo->beginTransaction();
            try {
                $migration->up($this->pdo);
                $this->repository->record($name, $batch);
                $this->pdo->commit();
                $ran[] = $name;
            } catch (\Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw new RuntimeException("Migration failed on [{$name}]: " . $e->getMessage(), 0, $e);
            }
        }

        return $ran;
    }

    /**
     * Rollback the last migration batch.
     *
     * @return list<string> Rolled back migration names
     */
    public function rollback(): array
    {
        $lastBatch = $this->repository->getLastBatchMigrations();
        if (empty($lastBatch)) {
            return [];
        }

        $rolledBack = [];
        foreach ($lastBatch as $name) {
            $migration = $this->resolveMigration($name);
            try {
                $migration->down($this->pdo);
                $this->repository->delete($name);
                $rolledBack[] = $name;
            } catch (\Throwable $e) {
                throw new RuntimeException("Rollback failed on [{$name}]: " . $e->getMessage(), 0, $e);
            }
        }

        return $rolledBack;
    }

    /**
     * @return list<string>
     */
    public function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsDir)) {
            return [];
        }

        $files = glob($this->migrationsDir . '/*.php') ?: [];
        $names = [];
        foreach ($files as $file) {
            $names[] = pathinfo($file, PATHINFO_FILENAME);
        }
        sort($names);
        return $names;
    }

    private function resolveMigration(string $name): MigrationInterface
    {
        $path = $this->migrationsDir . '/' . $name . '.php';
        if (!file_exists($path)) {
            throw new RuntimeException("Migration file [{$path}] not found");
        }

        require_once $path;

        $tokens = token_get_all(file_get_contents($path));
        $className = null;
        $namespace = '';

        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $ns = '';
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j] === ';') {
                        break;
                    }
                    if (is_array($tokens[$j])) {
                        $ns .= $tokens[$j][1];
                    }
                }
                $namespace = trim($ns);
            }

            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $className = $tokens[$j][1];
                        break 2;
                    }
                }
            }
        }

        $fqcn = $namespace !== '' ? $namespace . '\\' . $className : (string)$className;
        if (!class_exists($fqcn)) {
            throw new RuntimeException("Class [{$fqcn}] not found in [{$path}]");
        }

        $instance = new $fqcn();
        if (!$instance instanceof MigrationInterface) {
            throw new RuntimeException("Migration [{$fqcn}] must implement MigrationInterface");
        }

        return $instance;
    }
}
