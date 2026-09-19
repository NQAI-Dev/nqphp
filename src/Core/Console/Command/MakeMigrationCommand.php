<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:migration', description: 'Create a new database migration file')]
final class MakeMigrationCommand extends Command
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        parent::__construct();
        $this->kernel = $kernel;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The migration name (e.g. create_users_table)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $name */
        $name = $input->getArgument('name');
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($name)) ?? 'migration';

        $timestamp = date('Y_m_d_His');
        $fileName = sprintf('%s_%s.php', $timestamp, $cleanName);
        $className = sprintf('Migration_%s_%s', $timestamp, $cleanName);

        $migrationsDir = $this->kernel->getProjectDir() . '/migrations';
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0777, true);
        }

        $filePath = $migrationsDir . '/' . $fileName;

        $template = <<<PHP
<?php

declare(strict_types=1);

use Nqphp\Core\Migration\AbstractMigration;
use PDO;

final class {$className} extends AbstractMigration
{
    public function up(PDO \$pdo): void
    {
        // \$pdo->exec("CREATE TABLE IF NOT EXISTS {$cleanName} (id INTEGER PRIMARY KEY AUTOINCREMENT);");
    }

    public function down(PDO \$pdo): void
    {
        // \$pdo->exec("DROP TABLE IF EXISTS {$cleanName};");
    }

    public function getDescription(): string
    {
        return 'Migration {$name}';
    }
}
PHP;

        file_put_contents($filePath, $template);
        $output->writeln(sprintf('<info>Created migration:</info> %s', $filePath));

        return Command::SUCCESS;
    }
}
