<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\View\AssetCompiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'assets:build', description: 'Compile and bundle all feature scoped CSS and JS assets with manifest')]
final class AssetsBuildCommand extends Command
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        parent::__construct();
        $this->kernel = $kernel;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectDir = $this->kernel->getProjectDir();
        $compiler = new AssetCompiler($projectDir);

        $output->writeln('<info>Building production assets...</info>');
        $manifest = $compiler->compile();

        if (empty($manifest)) {
            $output->writeln('<comment>No feature assets found to compile.</comment>');
            return Command::SUCCESS;
        }

        foreach ($manifest as $source => $distUrl) {
            $output->writeln(sprintf('  <info>Compiled:</info> %s -> %s', $source, $distUrl));
        }

        $output->writeln(sprintf('<info>Assets build completed. Manifest written with %d entries.</info>', count($manifest)));
        return Command::SUCCESS;
    }
}
