<?php

declare(strict_types=1);

namespace Nqphp\Feature\Hello\Command;

use Nqphp\Core\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('hello:greet', 'Greet the world (or the name you pass).')]
class HelloGreetCommand extends Command
{
    protected function configure(): void
    {
        $this->setAliases(['hi']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello, world!');
        return Command::SUCCESS;
    }
}
