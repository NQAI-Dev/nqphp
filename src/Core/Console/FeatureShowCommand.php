<?php

declare(strict_types=1);

namespace Nqphp\Core\Console;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Kernel\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('feature:show', 'Show feature details')]
final class FeatureShowCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct("feature:show");
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The feature name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = (string) $input->getArgument('name');
        $features = $this->kernel->featureConfig()->load();

        if (!\in_array($name, $features->features(), true)) {
            $io->error("Feature '$name' not discovered.");
            return Command::FAILURE;
        }

        $io->title("Feature: $name");
        $config = $features->all($name);

        if ($config === []) {
            $io->note('No configuration keys.');
            return Command::SUCCESS;
        }

        ksort($config);
        $rows = [];
        foreach ($config as $key => $value) {
            $rows[] = [(string) $key, $this->formatValue($value)];
        }

        $io->section('Configuration');
        $io->table(['KEY', 'VALUE'], $rows);

        return Command::SUCCESS;
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (\is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $encoded === false ? get_debug_type($value) : $encoded;
    }
}
