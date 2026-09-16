<?php

declare(strict_types=1);

namespace Nqphp\Feature\Hello\Command;

use Nqphp\Core\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * `bin/console hello:greet [name]` — the simplest proof that command
 * auto-discovery works. Symmetric to GET /json/{name} from the HTTP
 * feature surface: greet the world by default, or whatever name you pass.
 *
 * The class-level #[AsCommand] attribute is what the discoverer keys on;
 * configure()/execute() are stock Symfony Console patterns.
 */
#[AsCommand(
    name: 'hello:greet',
    description: 'Greet the world (or the name you pass).',
    aliases: ['hi'],
)]
final class HelloCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Who to greet.', 'world');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = (string) $input->getArgument('name');
        $io->writeln(sprintf('<info>Hello %s!</info> 👋', $name));
        $io->writeln('<comment>nqphp CLI auto-discovery works.</comment>');
        return Command::SUCCESS;
    }
}
