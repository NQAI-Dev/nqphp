<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Routing;

use Nqphp\Core\Routing\RouteCollector;
use PHPUnit\Framework\TestCase;

class ResourceRouteBuilderTest extends TestCase
{
    public function testRegistersDefaultCrudRoutes(): void
    {
        $collector = new RouteCollector();
        $collector->resource('posts', 'App\\Controller\\PostController');
        $routes = $collector->getCollection();

        $this->assertNotNull($routes->get('posts.index'));
        $this->assertSame('/posts', $routes->get('posts.index')->getPath());
        $this->assertSame(['GET'], $routes->get('posts.index')->getMethods());

        $this->assertNotNull($routes->get('posts.store'));
        $this->assertSame('/posts', $routes->get('posts.store')->getPath());
        $this->assertSame(['POST'], $routes->get('posts.store')->getMethods());

        $this->assertNotNull($routes->get('posts.show'));
        $this->assertSame('/posts/{post}', $routes->get('posts.show')->getPath());
        $this->assertSame(['GET'], $routes->get('posts.show')->getMethods());
        $this->assertSame('\\d+', $routes->get('posts.show')->getRequirement('post'));

        $this->assertNotNull($routes->get('posts.update'));
        $this->assertSame(['PUT'], $routes->get('posts.update')->getMethods());

        $this->assertNotNull($routes->get('posts.patch'));
        $this->assertSame(['PATCH'], $routes->get('posts.patch')->getMethods());

        $this->assertNotNull($routes->get('posts.destroy'));
        $this->assertSame(['DELETE'], $routes->get('posts.destroy')->getMethods());
    }

    public function testResourceOnlyOption(): void
    {
        $collector = new RouteCollector();
        $collector->resource('users', 'App\\Controller\\UserController', [
            'only' => ['index', 'show'],
        ]);
        $routes = $collector->getCollection();

        $this->assertNotNull($routes->get('users.index'));
        $this->assertNotNull($routes->get('users.show'));
        $this->assertNull($routes->get('users.store'));
        $this->assertNull($routes->get('users.update'));
        $this->assertNull($routes->get('users.destroy'));
    }

    public function testResourceExceptOption(): void
    {
        $collector = new RouteCollector();
        $collector->resource('articles', 'App\\Controller\\ArticleController', [
            'except' => ['destroy', 'patch'],
        ]);
        $routes = $collector->getCollection();

        $this->assertNotNull($routes->get('articles.index'));
        $this->assertNotNull($routes->get('articles.store'));
        $this->assertNotNull($routes->get('articles.show'));
        $this->assertNotNull($routes->get('articles.update'));
        $this->assertNull($routes->get('articles.patch'));
        $this->assertNull($routes->get('articles.destroy'));
    }

    public function testResourceCustomIdPattern(): void
    {
        $collector = new RouteCollector();
        $collector->resource('orders', 'App\\Controller\\OrderController', [
            'id_pattern' => '[0-9a-f]{8}',
        ]);
        $routes = $collector->getCollection();

        $this->assertSame('[0-9a-f]{8}', $routes->get('orders.show')->getRequirement('order'));
    }
}
