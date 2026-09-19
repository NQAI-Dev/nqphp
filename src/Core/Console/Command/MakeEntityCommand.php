<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:entity', description: 'Create a new Entity in a vertical feature module')]
final class MakeEntityCommand extends Command
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        parent::__construct();
        $this->kernel = $kernel;
    }

    protected function configure(): void
    {
        $this->addArgument('feature', InputArgument::REQUIRED, 'Target feature module (e.g. Auth, Catalog)');
        $this->addArgument('name', InputArgument::REQUIRED, 'Entity class name (e.g. User, Product)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $feature */
        $feature = ucfirst(trim($input->getArgument('feature')));
        /** @var string $name */
        $name = ucfirst(trim($input->getArgument('name')));

        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name) . 's';

        $dir = $this->kernel->getProjectDir() . '/src/Feature/' . $feature . '/Entity';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/' . $name . '.php';
        if (file_exists($filePath)) {
            $output->writeln(sprintf('<error>Entity [%s] already exists!</error>', $filePath));
            return Command::FAILURE;
        }

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace App\Feature\\{$feature}\Entity;

use Nqphp\Core\Attribute\Entity;
use Nqphp\Core\Attribute\Id;
use Nqphp\Core\Attribute\Column;

#[Entity(table: '{$tableName}')]
final class {$name}
{
    #[Id]
    #[Column(type: 'integer')]
    public ?int \$id = null;

    #[Column(type: 'string', length: 255)]
    public string \$title;

    #[Column(type: 'datetime')]
    public string \$createdAt;

    public function __construct(string \$title)
    {
        \$this->title = \$title;
        \$this->createdAt = date('Y-m-d H:i:s');
    }
}
PHP;

        file_put_contents($filePath, $code);
        $output->writeln(sprintf('<info>Created entity:</info> %s (table: %s)', $filePath, $tableName));

        return Command::SUCCESS;
    }
}
