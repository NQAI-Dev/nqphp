<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Config\Repository;
use Nqphp\Core\Console\TableFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'debug:config',
    description: 'Отображает значения конфигурации приложения'
)]
class DebugConfigCommand extends Command
{
    public function __construct(
        private readonly Repository $repository,
        private readonly TableFormatter $tableFormatter = new TableFormatter()
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('key', InputArgument::OPTIONAL, 'Ключ конфигурации для детального просмотра');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');

        if (is_string($key) && $key !== '') {
            if (!$this->repository->has($key)) {
                $output->writeln(sprintf('<error>Ключ конфигурации "%s" не найден.</error>', $key));
                return Command::FAILURE;
            }

            $value = $this->repository->get($key);
            $output->writeln(sprintf('<info>Конфигурация для "%s":</info>', $key));

            if (is_array($value)) {
                $flattened = $this->flatten($value, $key);
                $rows = [];
                foreach ($flattened as $k => $v) {
                    $rows[] = [$k, $this->formatValue($v)];
                }
                $output->write($this->tableFormatter->format(['Ключ', 'Значение'], $rows));
            } else {
                $output->writeln(sprintf('  %s = %s', $key, $this->formatValue($value)));
            }

            return Command::SUCCESS;
        }

        $all = $this->repository->all();
        if (empty($all)) {
            $output->writeln('<comment>Конфигурация пуста.</comment>');
            return Command::SUCCESS;
        }

        $flattened = $this->flatten($all);
        $rows = [];
        foreach ($flattened as $k => $v) {
            $rows[] = [$k, $this->formatValue($v)];
        }

        $output->writeln(sprintf('<info>Всего параметров конфигурации: %d</info>', count($rows)));
        $output->write($this->tableFormatter->format(['Ключ', 'Значение'], $rows));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $fullKey));
            } else {
                $result[$fullKey] = $value;
            }
        }
        return $result;
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '<complex>';
    }
}
