<?php

declare(strict_types=1);

namespace Nqphp\Core\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Fluent builder for manually defining or augmenting routes.
 */
final class RouteCollector
{
    private RouteCollection $routes;
    private string $prefix = '';
    private string $namePrefix = '';

    public function __construct(?RouteCollection $routes = null)
    {
        $this->routes = $routes ?? new RouteCollection();
    }

    public function get(string $path, string|callable $handler, ?string $name = null): self
    {
        return $this->add(['GET'], $path, $handler, $name);
    }

    public function post(string $path, string|callable $handler, ?string $name = null): self
    {
        return $this->add(['POST'], $path, $handler, $name);
    }

    public function put(string $path, string|callable $handler, ?string $name = null): self
    {
        return $this->add(['PUT'], $path, $handler, $name);
    }

    public function delete(string $path, string|callable $handler, ?string $name = null): self
    {
        return $this->add(['DELETE'], $path, $handler, $name);
    }

    public function patch(string $path, string|callable $handler, ?string $name = null): self
    {
        return $this->add(['PATCH'], $path, $handler, $name);
    }

    /**
     * @param string[] $methods
     * @param array<string, string> $requirements
     * @param array<string, mixed> $defaults
     */
    public function add(
        array $methods,
        string $path,
        string|callable $handler,
        ?string $name = null,
        array $requirements = [],
        array $defaults = []
    ): self {
        $fullPath = rtrim($this->prefix, '/') . '/' . ltrim($path, '/');
        if ($fullPath === '') {
            $fullPath = '/';
        }

        $routeName = $name ?? (strtolower(implode('_', $methods)) . '_' . md5($fullPath));
        if ($this->namePrefix !== '') {
            $routeName = $this->namePrefix . $routeName;
        }

        $defaults['_controller'] = $handler;

        $route = new Route(
            $fullPath,
            $defaults,
            $requirements,
            [],
            '',
            [],
            $methods
        );

        $this->routes->add($routeName, $route);

        return $this;
    }

    /**
     * Group routes under a shared prefix and optional name prefix.
     */
    public function group(string $prefix, callable $callback, string $namePrefix = ''): void
    {
        $previousPrefix = $this->prefix;
        $previousNamePrefix = $this->namePrefix;

        $this->prefix = rtrim($previousPrefix, '/') . '/' . trim($prefix, '/');
        $this->namePrefix = $previousNamePrefix . $namePrefix;

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->namePrefix = $previousNamePrefix;
    }

    public function getCollection(): RouteCollection
    {
        return $this->routes;
    }
}
