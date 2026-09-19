<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:feature', description: 'Scaffold a complete vertical feature module')]
final class MakeFeatureCommand extends Command
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        parent::__construct();
        $this->kernel = $kernel;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the feature (e.g. Billing, Catalog)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $name */
        $name = $input->getArgument('name');
        $feature = ucfirst(trim($name));

        $featureDir = $this->kernel->getProjectDir() . '/src/Feature/' . $feature;
        if (is_dir($featureDir)) {
            $output->writeln(sprintf('<error>Feature [%s] already exists!</error>', $feature));
            return Command::FAILURE;
        }

        $dirs = [
            $featureDir . '/Controller',
            $featureDir . '/Entity',
            $featureDir . '/Service',
            $featureDir . '/View',
        ];

        foreach ($dirs as $dir) {
            mkdir($dir, 0777, true);
        }

        // 1. Controller scaffold
        $ctrlPath = $featureDir . '/Controller/' . $feature . 'Controller.php';
        $ctrlCode = <<<PHP
<?php

declare(strict_types=1);

namespace App\Feature\\{$feature}\Controller;

use Nqphp\Core\Attribute\Route;
use Nqphp\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class {$feature}Controller extends AbstractController
{
    #[Route('/' . strtolower('{$feature}'), name: '{$feature}_index', methods: ['GET'])]
    public function index(): Response
    {
        return \$this->json([
            'status' => 'ok',
            'feature' => '{$feature}',
            'message' => 'Scaffolded vertical slice module'
        ]);
    }
}
PHP;
        file_put_contents($ctrlPath, $ctrlCode);

        // 2. Service scaffold
        $servicePath = $featureDir . '/Service/' . $feature . 'Service.php';
        $serviceCode = <<<PHP
<?php

declare(strict_types=1);

namespace App\Feature\\{$feature}\Service;

final class {$feature}Service
{
    public function execute(): void
    {
        // Business logic here
    }
}
PHP;
        file_put_contents($servicePath, $serviceCode);

        $output->writeln(sprintf('<info>Created vertical feature module:</info> src/Feature/%s', $feature));
        $output->writeln(sprintf('  - Controller: %s', $ctrlPath));
        $output->writeln(sprintf('  - Service:    %s', $servicePath));

        return Command::SUCCESS;
    }
}
