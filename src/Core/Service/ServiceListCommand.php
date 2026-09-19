<?php

declare(strict_types=1);

namespace Nqphp\Core\Service;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('service:list', 'List discovered services')]
final class ServiceListCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct('service:list');
    }

    protected function configure(): void
    {
        $this->addOption('tag', 't', InputOption::VALUE_REQUIRED, 'Filter by tag');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $services = $this->kernel->serviceDiscoverer()->discover()->all();
        ksort($services);

        $tag = $input->getOption('tag');

        $rows = [];
        foreach ($services as $name => $descriptor) {
            if ($tag !== null && !\in_array($tag, $descriptor['tags'], true)) {
                continue;
            }
            $rows[] = [
                $name,
                $descriptor['scope'],
                $descriptor['class'],
                implode(', ', $descriptor['tags']),
            ];
        }

        $io->title('Discovered services');

        if ($rows === []) {
            $io->note($tag !== null ? "No services with tag '$tag'." : 'No services discovered.');
            return Command::SUCCESS;
        }

        $io->table(['NAME', 'SCOPE', 'CLASS', 'TAGS'], $rows);

        return Command::SUCCESS;
    }
}
