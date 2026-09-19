<?php

declare(strict_types=1);

namespace Nqphp\Core\Migration;

use PDO;

interface MigrationInterface
{
    /**
     * Apply the migration schema changes.
     */
    public function up(PDO $pdo): void;

    /**
     * Revert the migration schema changes.
     */
    public function down(PDO $pdo): void;

    /**
     * Human-readable migration description.
     */
    public function getDescription(): string;
}
