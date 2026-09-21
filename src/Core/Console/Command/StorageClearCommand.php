<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Filesystem\StorageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'storage:clear', description: 'Clear files from application storage disks')]
final class StorageClearCommand extends Command
{
    public function __construct(private readonly StorageManager $storageManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('disk', null, InputOption::VALUE_OPTIONAL, 'The specific storage disk to clear');
        $this->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Subdirectory path to clear', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $diskName = $input->getOption('disk');
        $path = (string) ($input->getOption('path') ?? '');

        if ($diskName !== null) {
            $output->writeln(sprintf('<info>Clearing storage disk [%s]...</info>', $diskName));
            $this->clearDisk($diskName, $path, $output);
            $output->writeln(sprintf('<info>Storage disk [%s] cleared successfully.</info>', $diskName));
            return Command::SUCCESS;
        }

        $disks = $this->storageManager->getDisks();
        if (empty($disks)) {
            $output->writeln('<comment>No mounted storage disks found.</comment>');
            return Command::SUCCESS;
        }

        foreach ($disks as $name) {
            $output->writeln(sprintf('<info>Clearing storage disk [%s]...</info>', $name));
            $this->clearDisk($name, $path, $output);
        }

        $output->writeln('<info>All mounted storage disks cleared successfully.</info>');

        return Command::SUCCESS;
    }

    private function clearDisk(string $diskName, string $path, OutputInterface $output): void
    {
        $disk = $this->storageManager->disk($diskName);
        $files = $disk->listContents($path);

        $deletedCount = 0;
        foreach ($files as $file) {
            if ($disk->delete($file)) {
                $deletedCount++;
            }
        }

        $output->writeln(sprintf('  <comment>Deleted %d file(s) from [%s].</comment>', $deletedCount, $diskName));
    }
}
