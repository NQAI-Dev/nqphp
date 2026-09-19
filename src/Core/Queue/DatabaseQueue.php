<?php

declare(strict_types=1);

namespace Nqphp\Core\Queue;

use PDO;

final class DatabaseQueue implements QueueInterface
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table = '_nq_jobs')
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
                    payload TEXT NOT NULL,
                    attempts INTEGER NOT NULL DEFAULT 0,
                    available_at INTEGER NOT NULL,
                    created_at INTEGER NOT NULL,
                    reserved_at INTEGER NULL
                )',
                $this->table
            );
        } else {
            $sql = sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    payload LONGTEXT NOT NULL,
                    attempts INT NOT NULL DEFAULT 0,
                    available_at INT NOT NULL,
                    created_at INT NOT NULL,
                    reserved_at INT NULL
                )',
                $this->table
            );
        }
        $this->pdo->exec($sql);
    }

    public function push(JobInterface $job, int $delay = 0): string
    {
        $now = time();
        $availableAt = $now + $delay;
        $payload = serialize($job);

        $stmt = $this->pdo->prepare(sprintf(
            'INSERT INTO %s (payload, attempts, available_at, created_at) VALUES (:payload, 0, :available_at, :created_at)',
            $this->table
        ));
        $stmt->execute([
            'payload' => $payload,
            'available_at' => $availableAt,
            'created_at' => $now,
        ]);
        $id = (string) $this->pdo->lastInsertId();
        $stmt->closeCursor();

        return $id;
    }

    public function pop(): ?JobEnvelope
    {
        $now = time();
        $this->pdo->beginTransaction();
        try {
            // Find next available job
            $stmt = $this->pdo->prepare(sprintf(
                'SELECT * FROM %s WHERE available_at <= :now AND reserved_at IS NULL ORDER BY id ASC LIMIT 1',
                $this->table
            ));
            $stmt->execute(['now' => $now]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$row) {
                $this->pdo->commit();
                return null;
            }

            // Reserve it
            $upd = $this->pdo->prepare(sprintf(
                'UPDATE %s SET reserved_at = :reserved, attempts = attempts + 1 WHERE id = :id',
                $this->table
            ));
            $upd->execute(['reserved' => $now, 'id' => $row['id']]);
            $upd->closeCursor();

            $this->pdo->commit();

            /** @var JobInterface $job */
            $job = unserialize($row['payload']);
            return new JobEnvelope(
                (string) $row['id'],
                $job,
                (int) $row['attempts'] + 1,
                (int) $row['available_at'],
                (int) $row['created_at']
            );
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function ack(string $jobId): void
    {
        $stmt = $this->pdo->prepare(sprintf('DELETE FROM %s WHERE id = :id', $this->table));
        $stmt->execute(['id' => $jobId]);
        $stmt->closeCursor();
    }

    public function fail(JobEnvelope $envelope, \Throwable $e): void
    {
        $job = $envelope->getJob();
        $attempts = $envelope->getAttempts();

        if ($attempts < $job->getMaxTries()) {
            // Retry after delay
            $retryAt = time() + $job->getRetryDelay();
            $stmt = $this->pdo->prepare(sprintf(
                'UPDATE %s SET reserved_at = NULL, available_at = :available_at WHERE id = :id',
                $this->table
            ));
            $stmt->execute([
                'available_at' => $retryAt,
                'id' => $envelope->getId(),
            ]);
            $stmt->closeCursor();
        } else {
            // Delete from queue or move to failed table
            $this->ack($envelope->getId());
        }
    }

    public function count(): int
    {
        $stmt = $this->pdo->query(sprintf('SELECT COUNT(*) FROM %s', $this->table));
        $cnt = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($stmt) {
            $stmt->closeCursor();
        }
        return $cnt;
    }
}
