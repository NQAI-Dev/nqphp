<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:controller', description: 'Create a new Controller in a vertical feature module')]
final class MakeControllerCommand extends Command
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
        $this->addArgument('name', InputArgument::REQUIRED, 'Controller class name (e.g. LoginController)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $feature */
        $feature = ucfirst(trim($input->getArgument('feature')));
        /** @var string $name */
        $name = ucfirst(trim($input->getArgument('name')));

        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $dir = $this->kernel->getProjectDir() . '/src/Feature/' . $feature . '/Controller';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/' . $name . '.php';
        if (file_exists($filePath)) {
            $output->writeln(sprintf('<error>Controller [%s] already exists!</error>', $filePath));
            return Command::FAILURE;
        }

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace App\Feature\\{$feature}\Controller;

use Nqphp\Core\Attribute\Route;
use Nqphp\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class {$name} extends AbstractController
{
    #[Route('/' . strtolower('{$feature}'), name: '{$feature}_action', methods: ['GET'])]
    public function index(): Response
    {
        return \$this->json(['status' => 'ok']);
    }
}
PHP;

        file_put_contents($filePath, $code);
        $output->writeln(sprintf('<info>Created controller:</info> %s', $filePath));

        return Command::SUCCESS;
    }
}
