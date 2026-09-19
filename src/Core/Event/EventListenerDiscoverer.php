<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

use Nqphp\Core\Attribute\EventListener;
use ReflectionClass;
use ReflectionMethod;

/**
 * Auto-discover #[EventListener] methods across feature + core
 * directories and subscribe them to an EventDispatcher.
 *
 * Discovery result is memoised per directory list. Listener methods
 * are expected to be static or on stateless classes — the discoverer
 * instantiates the declaring class once per subscription (no-arg
 * constructor assumed, mirroring middleware/hook discovery).
 */
final class EventListenerDiscoverer
{
    /** @var string[] */
    private readonly array $dirs;

    /** @var list<array{event: class-string, callable: callable, priority: int, method: string}>|null */
    private ?array $cache = null;

    /**
     * @param string[] $dirs Directories scanned for #[EventListener] classes.
     */
    public function __construct(array $dirs)
    {
        $this->dirs = $dirs;
    }

    /**
     * Discover all listeners, sorted by priority descending (stable
     * within the same priority — file scan order preserved).
     *
     * @return list<array{event: class-string, callable: callable, priority: int, method: string}>
     */
    public function discover(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $found = [];
        foreach ($this->dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $this->scanFile((string) $file, $found);
            }
        }

        \usort($found, fn (array $a, array $b) => $b['priority'] <=> $a['priority']);
        $this->cache = $found;

        return $found;
    }

    /**
     * Subscribe every discovered listener to the dispatcher.
     */
    public function attach(EventDispatcher $dispatcher): void
    {
        foreach ($this->discover() as $listener) {
            $dispatcher->listen($listener['event'], $listener['callable']);
        }
    }

    /**
     * @param list<array{event: class-string, callable: callable, priority: int, method: string}> $found
     */
    private function scanFile(string $path, array &$found): void
    {
        $contents = @file_get_contents($path);
        if ($contents === false || !str_contains($contents, 'EventListener')) {
            return;
        }
        if (!preg_match('/^\s*namespace\s+([\w\\\\]+);/m', $contents, $ns)) {
            return;
        }
        if (!preg_match_all('/(?:class|interface|trait)\s+(\w+)/', $contents, $classMatches)) {
            return;
        }
        $namespace = trim($ns[1], '\\');
        foreach ($classMatches[1] as $className) {
            $fqcn = $namespace . '\\' . $className;
            if (!class_exists($fqcn)) {
                require_once $path;
            }
            if (!class_exists($fqcn)) {
                continue;
            }
            $ref = new ReflectionClass($fqcn);
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(EventListener::class) as $attr) {
                    /** @var EventListener $instance */
                    $instance = $attr->newInstance();
                    $event = $this->resolveEventClass($method);
                    if ($event === null) {
                        continue;
                    }
                    $found[] = [
                        'event' => $event,
                        'callable' => $this->buildCallable($ref, $method),
                        'priority' => $instance->priority,
                        'method' => $ref->getName() . '::' . $method->getName(),
                    ];
                }
            }
        }
    }

    /**
     * The event class is taken from the single non-optional parameter
     * type. Methods without such a parameter are skipped.
     *
     * @return class-string|null
     */
    private function resolveEventClass(ReflectionMethod $method): ?string
    {
        $params = $method->getParameters();
        if (\count($params) !== 1) {
            return null;
        }
        $type = $params[0]->getType();
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin() || $params[0]->isOptional()) {
            return null;
        }

        /** @var class-string */
        return $type->getName();
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function buildCallable(ReflectionClass $class, ReflectionMethod $method): callable
    {
        if ($method->isStatic()) {
            /** @psalm-suppress PossiblyNullReference */
            return $method->getClosure(null);
        }
        $instance = $class->newInstance();

        /** @psalm-suppress PossiblyNullReference */
        return $method->getClosure($instance);
    }
}
