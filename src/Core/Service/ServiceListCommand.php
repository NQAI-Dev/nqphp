<?php

declare(strict_types=1);

namespace Nqphp\Core\Service;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('service:list', 'List discovered services')]
final class ServiceListCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct("service:list");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $services = $this->kernel->serviceDiscoverer()->discover()->all();
        ksort($services);

        $rows = [];
        foreach ($services as $name => $descriptor) {
            $rows[] = [$name, $descriptor['scope'], $descriptor['class']];
        }

        $io->title('Discovered services');
        $io->table(['NAME', 'SCOPE', 'CLASS'], $rows);

        if ($rows === []) {
            $io->note('No services discovered.');
        }

        return Command::SUCCESS;
    }
}
