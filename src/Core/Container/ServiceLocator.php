<?php

declare(strict_types=1);

namespace Nqphp\Core\Container;

use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Lightweight service locator with recursive constructor autowiring.
 *
 * Resolves services by class name or by user-bound aliases.
 * Supports singleton (default) and prototype scopes.
 * Implements PSR-11 ContainerInterface.
 *
 * Example:
 *
 *   $locator = new ServiceLocator();
 *   $locator->bind(LoggerInterface::class, FileLogger::class);
 *   $locator->bindFactory('clock', fn() => new SystemClock());
 *   $locator->singleton(CachePool::class);     // explicit singleton
 *   $locator->prototype(CommandBus::class);    // new instance each call
 *
 *   $logger = $locator->make(LoggerInterface::class);
 *   $cache  = $locator->make(CachePool::class);
 */
class ServiceLocator implements ContainerInterface
{
    /** @var array<string, class-string> alias → concrete class */
    private array $bindings = [];

    /** @var array<string, callable(): object> alias → factory */
    private array $factories = [];

    /** @var array<string, object> singleton instance store */
    private array $singletons = [];

    /** @var array<string, bool> ids that use prototype scope */
    private array $prototypeScopes = [];

    /** @var array<string> resolution stack for circular dependency detection */
    private array $resolutionStack = [];

    // ─── Registration ─────────────────────────────────────────────────────────

    /**
     * Bind an abstract name / interface to a concrete class.
     * Resolved as singleton unless overridden via prototype().
     *
     * @param class-string $abstract
     * @param class-string $concrete
     */
    public function bind(string $abstract, string $concrete): static
    {
        $this->bindings[$abstract] = $concrete;
        return $this;
    }

    /**
     * Bind a name to a factory closure.
     * Resolved as singleton unless overridden via prototype().
     *
     * @param callable(): object $factory
     */
    public function bindFactory(string $id, callable $factory): static
    {
        $this->factories[$id] = $factory;
        return $this;
    }

    /**
     * Register a pre-built singleton instance.
     *
     * @template T of object
     * @param class-string<T>|string $id
     * @param T $instance
     */
    public function instance(string $id, object $instance): static
    {
        $this->singletons[$id] = $instance;
        return $this;
    }

    /**
     * Mark a class/id as singleton scope (the default; provided for clarity).
     */
    public function singleton(string $id): static
    {
        unset($this->prototypeScopes[$id]);
        return $this;
    }

    /**
     * Mark a class/id as prototype scope (new instance every make() call).
     */
    public function prototype(string $id): static
    {
        $this->prototypeScopes[$id] = true;
        return $this;
    }

    // ─── Resolution ───────────────────────────────────────────────────────────

    /**
     * Resolve a service by id. Returns a cached singleton where applicable.
     *
     * @template T of object
     * @param class-string<T>|string $id
     * @return T
     */
    public function make(string $id): object
    {
        $isPrototype = isset($this->prototypeScopes[$id]);

        if (!$isPrototype && isset($this->singletons[$id])) {
            /** @var T $cached */
            $cached = $this->singletons[$id];
            return $cached;
        }

        $instance = $this->build($id);

        if (!$isPrototype) {
            $this->singletons[$id] = $instance;
        }

        /** @var T $instance */
        return $instance;
    }

    /**
     * PSR-11 compliant entry retrieval.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws NotFoundException No entry was found for **this** identifier.
     * @throws ContainerException Error while retrieving the entry.
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("No entry found for identifier \"{$id}\".");
        }

        try {
            return $this->make($id);
        } catch (\Throwable $e) {
            if ($e instanceof NotFoundException) {
                throw $e;
            }
            throw new ContainerException("Error while retrieving identifier \"{$id}\": {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Whether the locator can resolve the given id.
     */
    public function has(string $id): bool
    {
        return isset($this->singletons[$id])
            || isset($this->bindings[$id])
            || isset($this->factories[$id])
            || class_exists($id);
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private function build(string $id): object
    {
        // Circular dependency guard
        if (in_array($id, $this->resolutionStack, true)) {
            throw new LogicException(sprintf(
                'Circular dependency detected while resolving "%s". Stack: %s',
                $id,
                implode(' → ', [...$this->resolutionStack, $id])
            ));
        }

        // Pre-bound factory
        if (isset($this->factories[$id])) {
            $this->resolutionStack[] = $id;
            try {
                return ($this->factories[$id])();
            } finally {
                array_pop($this->resolutionStack);
            }
        }

        // Alias binding
        $concrete = $this->bindings[$id] ?? $id;

        if (!class_exists($concrete)) {
            throw new LogicException(sprintf(
                'Cannot resolve "%s": class "%s" does not exist.',
                $id,
                $concrete
            ));
        }

        $this->resolutionStack[] = $id;
        try {
            return $this->autowire($concrete);
        } finally {
            array_pop($this->resolutionStack);
        }
    }

    /**
     * Reflect on a concrete class and autowire its constructor parameters.
     *
     * @param class-string $class
     * @throws LogicException for unresolvable parameters
     */
    private function autowire(string $class): object
    {
        try {
            $ref = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new LogicException("Cannot reflect on class \"$class\": {$e->getMessage()}", 0, $e);
        }

        if (!$ref->isInstantiable()) {
            throw new LogicException("Class \"$class\" is not instantiable (abstract, interface, or private constructor).");
        }

        $constructor = $ref->getConstructor();
        if ($constructor === null) {
            return $ref->newInstance();
        }

        $args = array_map(
            fn (ReflectionParameter $param): mixed => $this->resolveParam($param, $class),
            $constructor->getParameters()
        );

        return $ref->newInstanceArgs($args);
    }

    private function resolveParam(ReflectionParameter $param, string $forClass): mixed
    {
        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            /** @var class-string $typeName */
            $typeName = $type->getName();
            if ($this->has($typeName)) {
                return $this->make($typeName);
            }
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        throw new LogicException(sprintf(
            'Cannot autowire parameter "$%s" of "%s": no type hint, no default, and not nullable.',
            $param->getName(),
            $forClass
        ));
    }
}
