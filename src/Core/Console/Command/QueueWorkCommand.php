<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Queue\DatabaseQueue;
use Nqphp\Core\Queue\Worker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'queue:work', description: 'Process background jobs from the queue')]
final class QueueWorkCommand extends Command
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        parent::__construct();
        $this->kernel = $kernel;
    }

    protected function configure(): void
    {
        $this->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Seconds to sleep when queue is empty', 2);
        $this->addOption('once', null, InputOption::VALUE_NONE, 'Process only one job and exit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pdo = $this->kernel->pdo();
        $queue = new DatabaseQueue($pdo);
        $worker = new Worker($queue);

        if ($input->getOption('once')) {
            $output->writeln('<info>Processing single job...</info>');
            $processed = $worker->processNextJob();
            $output->writeln($processed ? '<info>Job executed.</info>' : '<comment>No jobs to process.</comment>');
            return Command::SUCCESS;
        }

        $sleep = (int) $input->getOption('sleep');
        $output->writeln(sprintf('<info>Starting queue worker (sleep: %ds)...</info>', $sleep));
        $worker->work($sleep);

        return Command::SUCCESS;
    }
}
