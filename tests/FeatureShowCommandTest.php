<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Console\FeatureShowCommand;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class FeatureShowCommandTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/nqphp-feature-command-' . bin2hex(random_bytes(6));
        mkdir($this->projectDir . '/src/Feature/Blog', 0777, true);
        file_put_contents(
            $this->projectDir . '/src/Feature/Blog/config.php',
            "<?php\nreturn ['enabled' => true, 'cache_ttl' => 120, 'hosts' => ['api.example.test']];\n"
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    // ── existence / failure ────────────────────────────────────────────────

    public function testCommandFailsForUndiscoveredFeature(): void
    {
        // 'Empty' dir exists but has no config, no routes, no middleware, no commands
        mkdir($this->projectDir . '/src/Feature/Empty');
        $tester = $this->runCommand('Empty');

        self::assertStringContainsString('not discovered', $tester->getDisplay());
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    public function testCommandSucceedsForFeatureWithConfigOnly(): void
    {
        $tester = $this->runCommand('Blog');
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    // ── config section ─────────────────────────────────────────────────────

    public function testCommandReportsActualConfigurationValues(): void
    {
        $tester = $this->runCommand('Blog');
        $output = $tester->getDisplay();

        self::assertStringContainsString('Feature: Blog', $output);
        self::assertStringContainsString('Configuration', $output);
        self::assertStringContainsString('cache_ttl', $output);
        self::assertStringContainsString('120', $output);
        self::assertStringContainsString('enabled', $output);
        self::assertStringContainsString('true', $output);
        self::assertStringContainsString('["api.example.test"]', $output);
    }

    public function testConfigBoolFalseFormattedCorrectly(): void
    {
        mkdir($this->projectDir . '/src/Feature/Flags', 0777, true);
        file_put_contents(
            $this->projectDir . '/src/Feature/Flags/config.php',
            "<?php\nreturn ['debug' => false, 'rate' => 0];\n"
        );

        $output = $this->runCommand('Flags')->getDisplay();
        self::assertStringContainsString('false', $output);
        self::assertStringContainsString('0', $output);
    }

    public function testConfigNullValueFormattedCorrectly(): void
    {
        mkdir($this->projectDir . '/src/Feature/Nullable', 0777, true);
        file_put_contents(
            $this->projectDir . '/src/Feature/Nullable/config.php',
            "<?php\nreturn ['key' => null];\n"
        );

        $output = $this->runCommand('Nullable')->getDisplay();
        self::assertStringContainsString('null', $output);
    }

    public function testConfigEmptyArrayShowsNote(): void
    {
        mkdir($this->projectDir . '/src/Feature/Empty2', 0777, true);
        file_put_contents(
            $this->projectDir . '/src/Feature/Empty2/config.php',
            "<?php\nreturn [];\n"
        );

        $output = $this->runCommand('Empty2')->getDisplay();
        self::assertStringContainsString('present but empty', $output);
    }

    // ── section headings always present ───────────────────────────────────

    public function testAllSectionHeadingsPresent(): void
    {
        $output = $this->runCommand('Blog')->getDisplay();

        self::assertStringContainsString('Configuration', $output);
        self::assertStringContainsString('Routes', $output);
        self::assertStringContainsString('Middleware', $output);
        self::assertStringContainsString('Commands', $output);
    }

    // ── routes section ─────────────────────────────────────────────────────

    public function testRoutesDiscoveredForFeature(): void
    {
        $this->writeController('Blog', 'PostController', '/blog', 'blog', [
            ['path' => '/', 'method' => 'index', 'name' => 'list', 'httpMethods' => "['GET']"],
            ['path' => '/{id}', 'method' => 'show', 'name' => 'show', 'httpMethods' => "['GET']"],
        ]);

        $output = $this->runCommand('Blog')->getDisplay();
        self::assertStringContainsString('Routes', $output);
        self::assertStringContainsString('blog:list', $output);
        self::assertStringContainsString('/blog/', $output);
        self::assertStringContainsString('GET', $output);
        self::assertStringContainsString('blog:show', $output);
    }

    public function testNoRoutesNoteShownWhenNoneDiscovered(): void
    {
        $output = $this->runCommand('Blog')->getDisplay();
        self::assertStringContainsString('No routes discovered for this feature', $output);
    }

    // ── middleware section ─────────────────────────────────────────────────

    public function testMiddlewareDiscoveredForFeature(): void
    {
        $this->writeMiddleware('Blog', 'BlogAuthMiddleware', 'blog.auth', 10);

        $output = $this->runCommand('Blog')->getDisplay();
        self::assertStringContainsString('Middleware', $output);
        self::assertStringContainsString('blog.auth', $output);
        self::assertStringContainsString('10', $output);
        self::assertStringContainsString('handle', $output);
    }

    public function testNoMiddlewareNoteShownWhenNoneDiscovered(): void
    {
        $output = $this->runCommand('Blog')->getDisplay();
        self::assertStringContainsString('No middleware discovered for this feature', $output);
    }

    // ── commands section ───────────────────────────────────────────────────

    public function testCommandsDiscoveredForFeature(): void
    {
        $this->writeCommand('Blog', 'BlogPublishCommand', 'blog:publish', 'Publish a blog post');

        $output = $this->runCommand('Blog')->getDisplay();
        self::assertStringContainsString('Commands', $output);
        self::assertStringContainsString('blog:publish', $output);
        self::assertStringContainsString('Publish a blog post', $output);
    }

    public function testNoCommandsNoteShownWhenNoneDiscovered(): void
    {
        $output = $this->runCommand('Blog')->getDisplay();
        self::assertStringContainsString('No commands discovered for this feature', $output);
    }

    // ── feature with everything ────────────────────────────────────────────

    public function testFullFeatureShowsAllSections(): void
    {
        $this->writeController('Blog', 'PostController', '/blog', 'blog', [
            ['path' => '/', 'method' => 'index', 'name' => 'list', 'httpMethods' => "['GET']"],
        ]);
        $this->writeMiddleware('Blog', 'BlogAuthMiddleware', 'blog.auth', 5);
        $this->writeCommand('Blog', 'BlogPublishCommand', 'blog:publish', 'Publish posts');

        $output = $this->runCommand('Blog')->getDisplay();

        // Config
        self::assertStringContainsString('cache_ttl', $output);
        // Routes
        self::assertStringContainsString('blog:list', $output);
        // Middleware
        self::assertStringContainsString('blog.auth', $output);
        // Commands
        self::assertStringContainsString('blog:publish', $output);
        self::assertSame(Command::SUCCESS, $this->runCommand('Blog')->getStatusCode());
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function runCommand(string $name): CommandTester
    {
        $app = new Application('nqphp');
        $app->add(new FeatureShowCommand(new Kernel($this->projectDir)));
        $tester = new CommandTester($app->find('feature:show'));
        $tester->execute(['name' => $name]);
        return $tester;
    }

    /**
     * @param array<int, array{path: string, method: string, name: string, httpMethods: string}> $routes
     */
    private function writeController(
        string $feature,
        string $className,
        string $prefix,
        string $namePrefix,
        array $routes,
    ): void {
        $dir = $this->projectDir . "/src/Feature/$feature/Controller";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $methods = '';
        foreach ($routes as $r) {
            $methods .= <<<PHP

    #[\\Nqphp\\Core\\Attribute\\Route('{$r['path']}', methods: {$r['httpMethods']}, name: '{$r['name']}')]
    public function {$r['method']}(): string { return ''; }

PHP;
        }
        $php = <<<PHP
<?php
namespace Nqphp\\Feature\\{$feature}\\Controller;

use Nqphp\\Core\\Attribute\\Controller;

#[Controller('$prefix', namePrefix: '$namePrefix')]
final class $className
{{$methods}}
PHP;
        file_put_contents("$dir/$className.php", $php);
    }

    private function writeMiddleware(
        string $feature,
        string $className,
        string $name,
        int $order,
    ): void {
        $dir = $this->projectDir . "/src/Feature/$feature/Middleware";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $php = <<<PHP
<?php
namespace Nqphp\\Feature\\{$feature}\\Middleware;

use Nqphp\\Core\\Attribute\\Middleware;
use Symfony\\Component\\HttpFoundation\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

#[Middleware(name: '$name', order: $order)]
final class $className
{
    public function handle(Request \$request): ?Response { return null; }
}
PHP;
        file_put_contents("$dir/$className.php", $php);
    }

    private function writeCommand(
        string $feature,
        string $className,
        string $commandName,
        string $description,
    ): void {
        $dir = $this->projectDir . "/src/Feature/$feature/Command";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $php = <<<PHP
<?php
namespace Nqphp\\Feature\\{$feature}\\Command;

use Nqphp\\Core\\Attribute\\AsCommand;
use Symfony\\Component\\Console\\Command\\Command;
use Symfony\\Component\\Console\\Input\\InputInterface;
use Symfony\\Component\\Console\\Output\\OutputInterface;

#[AsCommand('$commandName', '$description')]
final class $className extends Command
{
    public function __construct() { parent::__construct('$commandName'); }
    protected function execute(InputInterface \$i, OutputInterface \$o): int { return Command::SUCCESS; }
}
PHP;
        file_put_contents("$dir/$className.php", $php);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
