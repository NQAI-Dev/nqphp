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

#[AsCommand(name: 'migrate', description: 'Run all pending database migrations')]
final class MigrateCommand extends Command
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
            mkdir($migrationsDir, 0777, true);
        }

        $repo = new MigrationRepository($pdo);
        $migrator = new Migrator($repo, $pdo, $migrationsDir);

        $output->writeln('<info>Running database migrations...</info>');
        $ran = $migrator->migrate();

        if (empty($ran)) {
            $output->writeln('<comment>Nothing to migrate.</comment>');
            return Command::SUCCESS;
        }

        foreach ($ran as $migration) {
            $output->writeln("  <info>Migrated:</info> {$migration}");
        }

        $output->writeln('<info>Database migration completed successfully.</info>');
        return Command::SUCCESS;
    }
}
