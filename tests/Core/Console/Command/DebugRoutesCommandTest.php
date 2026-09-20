<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Console\Command\DebugRoutesCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DebugRoutesCommandTest extends TestCase
{
    private RouteCollection $routes;
    private DebugRoutesCommand $command;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->routes = new RouteCollection();
        $this->routes->add(
            'users.index',
            new Route('/users', ['_controller' => 'App\Controller\UserController::index'], [], [], '', [], ['GET'])
        );
        $this->routes->add(
            'users.create',
            new Route('/users', ['_controller' => 'App\Controller\UserController::create'], [], [], '', [], ['POST'])
        );
        $this->routes->add(
            'posts.show',
            new Route('/posts/{id}', ['_controller' => 'App\Controller\PostController::show'], [], [], '', [], ['GET'])
        );

        $this->command = new DebugRoutesCommand($this->routes);
        $this->tester = new CommandTester($this->command);
    }

    public function testDisplaysAllRoutesInTable(): void
    {
        $status = $this->tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $output = $this->tester->getDisplay();

        $this->assertStringContainsString('Methods', $output);
        $this->assertStringContainsString('Path', $output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Handler', $output);

        $this->assertStringContainsString('GET', $output);
        $this->assertStringContainsString('/users', $output);
        $this->assertStringContainsString('users.index', $output);
        $this->assertStringContainsString('App\Controller\UserController::index', $output);

        $this->assertStringContainsString('POST', $output);
        $this->assertStringContainsString('users.create', $output);

        $this->assertStringContainsString('/posts/{id}', $output);
        $this->assertStringContainsString('posts.show', $output);
    }

    public function testFilterByMethod(): void
    {
        $this->tester->execute(['--method' => 'POST']);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('users.create', $output);
        $this->assertStringNotContainsString('users.index', $output);
        $this->assertStringNotContainsString('posts.show', $output);
    }

    public function testFilterByPath(): void
    {
        $this->tester->execute(['--path' => 'posts']);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('posts.show', $output);
        $this->assertStringNotContainsString('users.index', $output);
        $this->assertStringNotContainsString('users.create', $output);
    }

    public function testNoRoutesMatchFilter(): void
    {
        $this->tester->execute(['--path' => 'nonexistent']);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Маршруты не найдены', $output);
    }
}
