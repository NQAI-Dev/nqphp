<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Attribute\Controller;
use Nqphp\Core\Attribute\Route;
use Nqphp\Core\Routing\Router;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

#[Controller]
class StubBlogController
{
    #[Route('/blog', name: 'list', methods: ['GET'])]
    public function list(): Response { return new Response(); }

    #[Route('/blog/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): Response { return new Response(); }

    #[Route('/blog/{slug}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(string $slug): Response { return new Response(); }

    #[Route('/blog/{slug}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $slug): Response { return new Response(); }
}

#[Controller('/api/v1')]
class StubApiController
{
    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(): Response { return new Response(); }
}

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        // Feed the router a fixtures dir containing these test-only controllers
        $this->router = new Router([__DIR__]);
    }

    public function testDiscoverRegistersNamedRoutes(): void
    {
        $collection = $this->router->discover();

        $this->assertNotNull($collection->get('list'), 'route "list" must be registered');
        $this->assertNotNull($collection->get('show'), 'route "show" must be registered');
        $this->assertNotNull($collection->get('update'), 'route "update" must be registered');
        $this->assertNotNull($collection->get('delete'), 'route "delete" must be registered');
    }

    public function testRouteHasCorrectPath(): void
    {
        $collection = $this->router->discover();

        $this->assertSame('/blog', $collection->get('list')->getPath());
        $this->assertSame('/blog/{slug}', $collection->get('show')->getPath());
    }

    public function testRouteHasCorrectMethods(): void
    {
        $collection = $this->router->discover();

        $this->assertSame(['GET'], $collection->get('list')->getMethods());
        $this->assertSame(['PUT', 'PATCH'], $collection->get('update')->getMethods());
        $this->assertSame(['DELETE'], $collection->get('delete')->getMethods());
    }

    public function testControllerPrefixIsPrependedToPath(): void
    {
        $collection = $this->router->discover();

        $this->assertNotNull($collection->get('api:v1:status'), 'route "api:v1:status" must be registered');
        $this->assertSame('/api/v1/status', $collection->get('api:v1:status')->getPath());
    }

    public function testCollectionCountsAreConsistent(): void
    {
        $collection = $this->router->discover();
        $this->assertGreaterThanOrEqual(5, count($collection->all()));
    }
}
