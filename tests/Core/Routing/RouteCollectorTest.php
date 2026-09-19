<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Routing;

use Nqphp\Core\Routing\RouteCollector;
use PHPUnit\Framework\TestCase;

class RouteCollectorTest extends TestCase
{
    private RouteCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new RouteCollector();
    }

    public function testAddBasicRoutes(): void
    {
        $this->collector->get('/users', 'UserController::index', 'users.index');
        $this->collector->post('/users', 'UserController::create', 'users.create');
        $this->collector->put('/users/{id}', 'UserController::update', 'users.update');
        $this->collector->delete('/users/{id}', 'UserController::delete', 'users.delete');
        $this->collector->patch('/users/{id}', 'UserController::patch', 'users.patch');

        $routes = $this->collector->getCollection();

        $this->assertCount(5, $routes);

        $index = $routes->get('users.index');
        $this->assertNotNull($index);
        $this->assertSame('/users', $index->getPath());
        $this->assertSame(['GET'], $index->getMethods());
        $this->assertSame('UserController::index', $index->getDefault('_controller'));

        $create = $routes->get('users.create');
        $this->assertNotNull($create);
        $this->assertSame(['POST'], $create->getMethods());

        $delete = $routes->get('users.delete');
        $this->assertNotNull($delete);
        $this->assertSame(['DELETE'], $delete->getMethods());
    }

    public function testRouteGrouping(): void
    {
        $this->collector->group('/api/v1', function (RouteCollector $api) {
            $api->get('/status', 'StatusController::get', 'status');

            $api->group('/admin', function (RouteCollector $admin) {
                $admin->get('/dashboard', 'AdminController::dashboard', 'dashboard');
            }, 'admin.');
        }, 'api.');

        $routes = $this->collector->getCollection();

        $this->assertCount(2, $routes);

        $status = $routes->get('api.status');
        $this->assertNotNull($status);
        $this->assertSame('/api/v1/status', $status->getPath());

        $dashboard = $routes->get('api.admin.dashboard');
        $this->assertNotNull($dashboard);
        $this->assertSame('/api/v1/admin/dashboard', $dashboard->getPath());
    }

    public function testRequirementsAndDefaults(): void
    {
        $this->collector->add(
            ['GET'],
            '/posts/{id}',
            'PostController::show',
            'posts.show',
            ['id' => '\d+'],
            ['format' => 'json']
        );

        $routes = $this->collector->getCollection();
        $route = $routes->get('posts.show');

        $this->assertNotNull($route);
        $this->assertSame('\d+', $route->getRequirement('id'));
        $this->assertSame('json', $route->getDefault('format'));
        $this->assertSame('PostController::show', $route->getDefault('_controller'));
    }
}
