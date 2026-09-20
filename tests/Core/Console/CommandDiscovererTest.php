<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console;

use Nqphp\Core\Attribute\AsCommand;
use Nqphp\Core\Console\CommandDiscoverer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandDiscovererTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_cmd_test_' . uniqid();
        @mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        @rmdir($this->tempDir);
    }

    public function testDiscoverCommands(): void
    {
        $file1 = $this->tempDir . '/TestCommand.php';
        file_put_contents($file1, <<<'PHP'
<?php

namespace Nqphp\Tests\Core\Console\Fixtures;

use Nqphp\Core\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'app:test-cmd', description: 'Test command description', aliases: ['app:tc'])]
class TestCommand extends Command
{
}
PHP
        );

        $file2 = $this->tempDir . '/IgnoredClass.php';
        file_put_contents($file2, <<<'PHP'
<?php

namespace Nqphp\Tests\Core\Console\Fixtures;

class IgnoredClass
{
}
PHP
        );

        $discoverer = new CommandDiscoverer([$this->tempDir]);
        $commands = $discoverer->discover();

        $this->assertCount(1, $commands);
        $cmd = $commands[0];
        $this->assertSame('app:test-cmd', $cmd->getName());
        $this->assertSame('Test command description', $cmd->getDescription());
        $this->assertSame(['app:tc'], $cmd->getAliases());
    }

    public function testHandlesNonExistentDirectory(): void
    {
        $discoverer = new CommandDiscoverer(['/path/to/missing/dir_' . uniqid()]);
        $commands = $discoverer->discover();

        $this->assertSame([], $commands);
    }
}
