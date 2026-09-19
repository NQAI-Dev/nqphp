<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Cache\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cache:clear', description: 'Clear application cache stores')]
final class CacheClearCommand extends Command
{
    public function __construct(private readonly Cache $cache)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('store', null, InputOption::VALUE_OPTIONAL, 'The specific cache store to clear');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $input->getOption('store');

        if ($store !== null) {
            $output->writeln(sprintf('<info>Clearing cache store [%s]...</info>', $store));
            $this->cache->store($store)->clear();
            $output->writeln(sprintf('<info>Cache store [%s] cleared successfully.</info>', $store));
            return Command::SUCCESS;
        }

        $stores = $this->cache->stores();
        if (empty($stores)) {
            $this->cache->cache()->clear();
            $output->writeln('<info>Default cache cleared successfully.</info>');
            return Command::SUCCESS;
        }

        foreach ($stores as $storeName) {
            $output->writeln(sprintf('<info>Clearing cache store [%s]...</info>', $storeName));
            $this->cache->store($storeName)->clear();
        }

        $output->writeln('<info>All active cache stores cleared successfully.</info>');

        return Command::SUCCESS;
    }
}
