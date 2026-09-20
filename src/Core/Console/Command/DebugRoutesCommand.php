<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Console\TableFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouteCollection;

#[AsCommand(
    name: 'debug:routes',
    description: 'Отображает список всех зарегистрированных маршрутов приложения'
)]
class DebugRoutesCommand extends Command
{
    public function __construct(
        private readonly RouteCollection $routes,
        private readonly TableFormatter $tableFormatter = new TableFormatter()
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('method', 'm', InputOption::VALUE_REQUIRED, 'Фильтрация по HTTP-методу (GET, POST, etc.)')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Фильтрация по подстроке пути');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $methodFilter = $input->getOption('method');
        if (is_string($methodFilter) && $methodFilter !== '') {
            $methodFilter = strtoupper(trim($methodFilter));
        } else {
            $methodFilter = null;
        }

        $pathFilter = $input->getOption('path');
        if (is_string($pathFilter) && $pathFilter !== '') {
            $pathFilter = trim($pathFilter);
        } else {
            $pathFilter = null;
        }

        $rows = [];

        foreach ($this->routes->all() as $name => $route) {
            $methods = $route->getMethods();
            $methodsStr = !empty($methods) ? implode('|', $methods) : 'ANY';
            $path = $route->getPath();
            $defaults = $route->getDefaults();
            $handler = $defaults['_controller'] ?? $defaults['_method'] ?? 'Closure';

            if ($methodFilter !== null && !empty($methods) && !in_array($methodFilter, $methods, true)) {
                continue;
            }

            if ($pathFilter !== null && !str_contains($path, $pathFilter)) {
                continue;
            }

            $rows[] = [$methodsStr, $path, (string) $name, (string) $handler];
        }

        if (empty($rows)) {
            $output->writeln('<comment>Маршруты не найдены по указанным критериям.</comment>');
            return Command::SUCCESS;
        }

        $headers = ['Methods', 'Path', 'Name', 'Handler'];
        $output->writeln($this->tableFormatter->format($headers, $rows));

        return Command::SUCCESS;
    }
}
