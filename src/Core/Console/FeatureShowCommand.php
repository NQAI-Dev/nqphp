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

#[AsCommand('feature:show', 'Show feature details: config, routes, middleware, commands')]
final class FeatureShowCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct('feature:show');
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The feature name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = (string) $input->getArgument('name');

        // Validate: feature must have a discoverable config entry OR at least
        // one discovered route/middleware/command belonging to it.
        $featureConfig = $this->kernel->featureConfig()->load();
        $knownFeatures = $featureConfig->features();

        // Collect routes for this feature (route names starting with "<name>:" or
        // controller classes containing "\Feature\<Name>\").
        $routes = $this->collectRoutes($name);

        // Collect middleware for this feature.
        $middlewares = $this->collectMiddleware($name);

        // Collect commands for this feature.
        $commands = $this->collectCommands($name);

        $hasConfig = \in_array($name, $knownFeatures, true);
        $hasAny    = $hasConfig || $routes !== [] || $middlewares !== [] || $commands !== [];

        if (!$hasAny) {
            $io->error("Feature '$name' not discovered.");
            return Command::FAILURE;
        }

        $io->title("Feature: $name");

        // ── Configuration ──────────────────────────────────────────────────
        $io->section('Configuration');
        if (!$hasConfig) {
            $io->note('No config.php found for this feature.');
        } else {
            $config = $featureConfig->all($name);
            if ($config === []) {
                $io->note('config.php present but empty.');
            } else {
                ksort($config);
                $rows = [];
                foreach ($config as $key => $value) {
                    $rows[] = [(string) $key, $this->formatValue($value)];
                }
                $io->table(['KEY', 'VALUE'], $rows);
            }
        }

        // ── Routes ─────────────────────────────────────────────────────────
        $io->section('Routes');
        if ($routes === []) {
            $io->note('No routes discovered for this feature.');
        } else {
            $io->table(['NAME', 'PATH', 'METHODS'], $routes);
        }

        // ── Middleware ─────────────────────────────────────────────────────
        $io->section('Middleware');
        if ($middlewares === []) {
            $io->note('No middleware discovered for this feature.');
        } else {
            $io->table(['NAME', 'ORDER', 'METHOD'], $middlewares);
        }

        // ── Commands ───────────────────────────────────────────────────────
        $io->section('Commands');
        if ($commands === []) {
            $io->note('No commands discovered for this feature.');
        } else {
            $io->table(['NAME', 'DESCRIPTION'], $commands);
        }

        return Command::SUCCESS;
    }

    /**
     * Collect routes whose controller class belongs to this feature namespace.
     *
     * @return list<array{string, string, string}>
     */
    private function collectRoutes(string $featureName): array
    {
        $collection = $this->kernel->getRouteCollection();
        $rows = [];
        foreach ($collection->all() as $routeName => $route) {
            $controller = (string) ($route->getDefault('_controller') ?? '');
            if (!$this->belongsToFeature($controller, $featureName)) {
                continue;
            }
            $methods = $route->getMethods();
            $rows[] = [
                $routeName,
                $route->getPath(),
                $methods !== [] ? implode('|', $methods) : 'ANY',
            ];
        }
        return $rows;
    }

    /**
     * Collect middleware whose class belongs to this feature namespace.
     *
     * @return list<array{string, string, string}>
     */
    private function collectMiddleware(string $featureName): array
    {
        $all  = $this->kernel->middlewareDiscoverer()->discover()->all();
        $rows = [];
        foreach ($all as $descriptor) {
            [$instance] = $descriptor['callable'];
            if (!$this->belongsToFeature($instance::class, $featureName)) {
                continue;
            }
            $rows[] = [
                $descriptor['name'],
                (string) $descriptor['order'],
                $descriptor['callable'][1],
            ];
        }
        return $rows;
    }

    /**
     * Collect console commands whose class belongs to this feature namespace.
     *
     * @return list<array{string, string}>
     */
    private function collectCommands(string $featureName): array
    {
        $discoverer = new \Nqphp\Core\Console\CommandDiscoverer(
            $this->kernel->commandDirs()
        );
        $rows = [];
        foreach ($discoverer->discover() as $cmd) {
            if (!$this->belongsToFeature($cmd::class, $featureName)) {
                continue;
            }
            $rows[] = [
                (string) $cmd->getName(),
                $cmd->getDescription(),
            ];
        }
        return $rows;
    }

    /**
     * True when the given FQCN contains `\Feature\<FeatureName>\` (case-sensitive).
     */
    private function belongsToFeature(string $fqcn, string $featureName): bool
    {
        return str_contains($fqcn, '\\Feature\\' . $featureName . '\\');
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
