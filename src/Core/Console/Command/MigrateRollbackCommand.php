<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Migration\MigrationRepository;
use Nqphp\Core\Migration\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrate:rollback', description: 'Rollback the last database migration batch')]
final class MigrateRollbackCommand extends Command
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        parent::__construct();
        $this->kernel = $kernel;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pdo = $this->kernel->pdo();
        $migrationsDir = $this->kernel->getProjectDir() . '/migrations';

        if (!is_dir($migrationsDir)) {
            $output->writeln('<comment>No migrations directory found.</comment>');
            return Command::SUCCESS;
        }

        $repo = new MigrationRepository($pdo);
        $migrator = new Migrator($repo, $pdo, $migrationsDir);

        $output->writeln('<info>Rolling back last migration batch...</info>');
        $rolled = $migrator->rollback();

        if (empty($rolled)) {
            $output->writeln('<comment>Nothing to rollback.</comment>');
            return Command::SUCCESS;
        }

        foreach ($rolled as $migration) {
            $output->writeln("  <info>Rolled back:</info> {$migration}");
        }

        $output->writeln('<info>Rollback completed successfully.</info>');
        return Command::SUCCESS;
    }
}
