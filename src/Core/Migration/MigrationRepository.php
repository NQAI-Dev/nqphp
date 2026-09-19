<?php

declare(strict_types=1);

namespace Nqphp\Core\Migration;

use PDO;

final class MigrationRepository
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table = '_nq_migrations')
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    batch INTEGER NOT NULL,
                    executed_at DATETIME NOT NULL
                )',
                $this->table
            );
        } else {
            $sql = sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    batch INT NOT NULL,
                    executed_at DATETIME NOT NULL
                )',
                $this->table
            );
        }
        $this->pdo->exec($sql);
    }

    /**
     * @return list<string>
     */
    public function getExecutedMigrations(): array
    {
        $stmt = $this->pdo->query(sprintf('SELECT migration FROM %s ORDER BY id ASC', $this->table));
        $res = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        if ($stmt) {
            $stmt->closeCursor();
            unset($stmt);
        }
        return $res;
    }

    public function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query(sprintf('SELECT MAX(batch) FROM %s', $this->table));
        $max = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($stmt) {
            $stmt->closeCursor();
            unset($stmt);
        }
        return $max + 1;
    }

    public function record(string $migration, int $batch): void
    {
        $stmt = $this->pdo->prepare(sprintf(
            'INSERT INTO %s (migration, batch, executed_at) VALUES (:migration, :batch, :executed_at)',
            $this->table
        ));
        $stmt->execute([
            'migration' => $migration,
            'batch' => $batch,
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
        $stmt->closeCursor();
        unset($stmt);
    }

    public function delete(string $migration): void
    {
        $stmt = $this->pdo->prepare(sprintf('DELETE FROM %s WHERE migration = :migration', $this->table));
        $stmt->execute(['migration' => $migration]);
        $stmt->closeCursor();
        unset($stmt);
    }

    /**
     * @return list<string>
     */
    public function getLastBatchMigrations(): array
    {
        $stmt = $this->pdo->query(sprintf(
            'SELECT migration FROM %s WHERE batch = (SELECT MAX(batch) FROM %s) ORDER BY id DESC',
            $this->table,
            $this->table
        ));
        $res = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        if ($stmt) {
            $stmt->closeCursor();
            unset($stmt);
        }
        return $res;
    }
}
