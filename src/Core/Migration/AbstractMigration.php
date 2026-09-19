<?php

declare(strict_types=1);

namespace Nqphp\Core\Migration;

use PDO;

abstract class AbstractMigration implements MigrationInterface
{
    public function getDescription(): string
    {
        return static::class;
    }

    protected function execute(PDO $pdo, string $sql): int|false
    {
        return $pdo->exec($sql);
    }
}
