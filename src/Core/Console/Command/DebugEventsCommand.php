<?php

declare(strict_types=1);

namespace Nqphp\Core\Console\Command;

use Closure;
use Nqphp\Core\Console\TableFormatter;
use Nqphp\Core\Event\EventDispatcher;
use ReflectionFunction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'debug:events',
    description: 'Отображает список всех зарегистрированных событий и их обработчиков'
)]
class DebugEventsCommand extends Command
{
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly TableFormatter $tableFormatter = new TableFormatter()
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('event', 'e', InputOption::VALUE_REQUIRED, 'Фильтрация по подстроке имени класса события');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eventFilter = $input->getOption('event');
        if (is_string($eventFilter) && $eventFilter !== '') {
            $eventFilter = trim($eventFilter);
        } else {
            $eventFilter = null;
        }

        $events = $this->eventDispatcher->getRegisteredEvents();
        sort($events);

        $rows = [];

        foreach ($events as $eventClass) {
            if ($eventFilter !== null && !str_contains($eventClass, $eventFilter)) {
                continue;
            }

            $listeners = $this->eventDispatcher->getListeners($eventClass);
            $order = 1;

            foreach ($listeners as $listener) {
                $handlerName = $this->describeListener($listener);
                $rows[] = [$eventClass, (string) $order++, $handlerName];
            }
        }

        if (empty($rows)) {
            $output->writeln('<info>События не найдены.</info>');
            return Command::SUCCESS;
        }

        $headers = ['Event', 'Order', 'Listener / Callable'];
        $output->write($this->tableFormatter->format($headers, $rows));

        return Command::SUCCESS;
    }

    private function describeListener(callable $listener): string
    {
        if (is_array($listener)) {
            $target = is_object($listener[0]) ? get_class($listener[0]) : (string) $listener[0];
            return $target . '::' . $listener[1];
        }

        if ($listener instanceof Closure) {
            $ref = new ReflectionFunction($listener);
            $file = $ref->getFileName();
            $line = $ref->getStartLine();
            if ($file && $line) {
                return 'Closure (' . basename($file) . ':' . $line . ')';
            }
            return 'Closure';
        }

        if (is_string($listener)) {
            return $listener;
        }

        if (is_object($listener)) {
            return get_class($listener) . '::__invoke';
        }

        return 'Unknown';
    }
}
