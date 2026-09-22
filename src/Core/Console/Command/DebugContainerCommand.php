<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Nqphp\Core\Container\ServiceLocator;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command for inspecting services, bindings, and singletons in ServiceLocator.
 */
class DebugContainerCommand extends Command
{
    protected static $defaultName = 'debug:container';

    public function __construct(
        private readonly ?ServiceLocator $container = null
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('debug:container')
            ->setDescription('Displays registered services, aliases, factories, and singletons in the DI container')
            ->addArgument('filter', InputArgument::OPTIONAL, 'Filter services by name/alias or class name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = $this->container;
        if ($container === null) {
            $output->writeln('<error>No ServiceLocator container instance available.</error>');
            return Command::FAILURE;
        }

        $filter = $input->getArgument('filter');
        $filter = is_string($filter) ? trim($filter) : null;

        $ref = new ReflectionClass($container);

        $getProp = function (string $name) use ($ref, $container): array {
            if (!$ref->hasProperty($name)) {
                return [];
            }
            $prop = $ref->getProperty($name);
            return (array) $prop->getValue($container);
        };

        $bindings = $getProp('bindings');
        $factories = $getProp('factories');
        $singletons = $getProp('singletons');
        $prototypeScopes = $getProp('prototypeScopes');

        $allIds = array_unique([
            ...array_keys($bindings),
            ...array_keys($factories),
            ...array_keys($singletons),
            ...array_keys($prototypeScopes),
        ]);
        sort($allIds);

        $rows = [];
        foreach ($allIds as $id) {
            if ($filter !== null && stripos($id, $filter) === false) {
                $concrete = $bindings[$id] ?? '';
                if (stripos($concrete, $filter) === false) {
                    continue;
                }
            }

            $scope = isset($prototypeScopes[$id]) ? 'prototype' : 'singleton';
            $instantiated = isset($singletons[$id]) ? 'yes' : 'no';

            $type = 'service';
            if (isset($factories[$id])) {
                $type = 'factory';
            } elseif (isset($bindings[$id])) {
                $type = 'alias';
            }

            $target = $bindings[$id] ?? (isset($singletons[$id]) ? get_class($singletons[$id]) : '-');

            $rows[] = [
                $id,
                $type,
                $target,
                $scope,
                $instantiated,
            ];
        }

        if (empty($rows)) {
            $msg = $filter !== null
                ? sprintf('No services matching "%s" found in container.', $filter)
                : 'No services registered in container.';
            $output->writeln("<comment>{$msg}</comment>");
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Service / Identifier', 'Type', 'Target / Class', 'Scope', 'Instantiated']);
        $table->setRows($rows);
        $table->render();

        return Command::SUCCESS;
    }
}
