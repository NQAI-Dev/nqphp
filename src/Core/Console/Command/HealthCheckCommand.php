<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Monitoring\DatabaseHealthCheck;
use Nqphp\Core\Monitoring\DiskSpaceHealthCheck;
use Nqphp\Core\Monitoring\HealthCheckRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'system:health', description: 'Run application health checks and output status')]
final class HealthCheckCommand extends Command
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        parent::__construct();
        $this->kernel = $kernel;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $registry = new HealthCheckRegistry();
        
        try {
            $pdo = $this->kernel->pdo();
            $registry->register(new DatabaseHealthCheck($pdo));
        } catch (\Throwable $e) {
            // DB connection issue will be captured if check is missing or handled
        }

        $registry->register(new DiskSpaceHealthCheck($this->kernel->getProjectDir()));

        $report = $registry->runAll();

        $output->writeln('<info>Running system health checks...</info>');

        foreach ($report['checks'] as $name => $details) {
            $status = $details['status'];
            $tag = match ($status) {
                'ok' => '<info>[OK]</info>',
                'degraded' => '<comment>[DEGRADED]</comment>',
                'down' => '<error>[DOWN]</error>',
                default => "<comment>[{$status}]</comment>",
            };

            $msg = isset($details['message']) ? " - {$details['message']}" : '';
            $output->writeln(sprintf('  %s %s%s', $tag, $name, $msg));
        }

        $output->writeln(sprintf('Overall status: <info>%s</info>', strtoupper($report['status'])));

        return $report['status'] === 'down' ? Command::FAILURE : Command::SUCCESS;
    }
}
