<?php

declare(strict_types=1);

namespace Nqphp\Core\Entity;

use Nqphp\Core\Attribute\Column;
use Nqphp\Core\Attribute\Entity;
use Nqphp\Core\Attribute\Id;
use Nqphp\Core\Entity\Driver\DriverInterface;
use Nqphp\Core\Entity\Driver\InMemoryDriver;
use ReflectionClass;
use ReflectionProperty;

final class EntityManager
{
    /** @var array<string, true> */
    private array $schemasEnsured = [];

    public function __construct(
        private readonly EntityDiscoverer $discoverer,
        private readonly DriverInterface $driver = new InMemoryDriver(),
    ) {
    }

    /**
     * Persist a new or modified entity immediately through the configured driver.
     *
     * @template T of object
     * @param T $entity
     * @return T
     */
    public function persist(object $entity): object
    {
        $reflection = new ReflectionClass($entity);
        $entityName = $this->entityNameFor($entity);
        $mapping = $this->mappingFor($reflection);
        $this->ensureSchema($entityName, $mapping);

        $data = [];
        foreach ($mapping as $propertyName => $metadata) {
            $property = $reflection->getProperty($propertyName);
            if (!$property->isInitialized($entity)) {
                continue;
            }
            $value = $property->getValue($entity);
            if ($metadata['id'] && $value === null) {
                continue;
            }
            $data[$metadata['name']] = $this->toStorageValue($value, $metadata['type']);
        }

        $id = $this->driver->persist($entityName, $data);
        $idProperty = $this->idPropertyFor($reflection);
        $idProperty->setValue($entity, $id);

        return $entity;
    }

    /** Drivers currently persist immediately; retained as a Unit-of-Work-compatible API. */
    public function flush(): void
    {
    }

    public function remove(object $entity): bool
    {
        $entityName = $this->entityNameFor($entity);
        $reflection = new ReflectionClass($entity);
        $idProperty = $this->idPropertyFor($reflection);
        $id = $idProperty->getValue($entity);
        if ($id === null) {
            throw new \RuntimeException(sprintf(
                'Cannot remove %s: its #[Id] is null (entity was never persisted).',
                $entityName
            ));
        }

        return $this->driver->delete($entityName, (int) $id);
    }

    /** @return list<object> */
    public function findAll(string $entityClass): array
    {
        return $this->findBy($entityClass, []);
    }

    /** @return list<object> */
    public function findBy(
        string $entityClass,
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $reflection = new ReflectionClass($entityClass);
        $entityName = $this->nameFor($entityClass);
        $mapping = $this->mappingFor($reflection);
        $this->ensureSchema($entityName, $mapping);

        $rows = $this->driver->findBy(
            $entityName,
            $this->mapCriteria($criteria, $mapping),
            $this->mapOrderBy($orderBy ?? [], $mapping),
            $limit,
            $offset,
        );

        return array_map(
            fn (array $row): object => $this->hydrate($reflection, $mapping, $row),
            $rows,
        );
    }

    public function findOneBy(string $entityClass, array $criteria): ?object
    {
        $matches = $this->findBy($entityClass, $criteria, null, 1);
        return $matches[0] ?? null;
    }

    public function count(string $entityClass, array $criteria = []): int
    {
        $reflection = new ReflectionClass($entityClass);
        $mapping = $this->mappingFor($reflection);
        $entityName = $this->nameFor($entityClass);
        $this->ensureSchema($entityName, $mapping);

        return $this->driver->count($entityName, $this->mapCriteria($criteria, $mapping));
    }

    /**
     * @param array<string, array{name: string, type: string, nullable: bool, length: int|null, unique: bool, default: mixed, id: bool}> $mapping
     */
    private function ensureSchema(string $entityName, array $mapping): void
    {
        if (isset($this->schemasEnsured[$entityName])) {
            return;
        }

        $columns = [];
        foreach ($mapping as $metadata) {
            $columns[$metadata['name']] = $metadata;
        }
        $this->driver->ensureSchema($entityName, $columns);
        $this->schemasEnsured[$entityName] = true;
    }

    /**
     * @return array<string, array{name: string, type: string, nullable: bool, length: int|null, unique: bool, default: mixed, id: bool}>
     */
    private function mappingFor(ReflectionClass $class): array
    {
        $this->idPropertyFor($class);
        $mapping = [];
        foreach ($class->getProperties() as $property) {
            $id = count($property->getAttributes(Id::class)) > 0;
            $columnAttributes = $property->getAttributes(Column::class);
            if (!$id && count($columnAttributes) === 0) {
                continue;
            }

            /** @var Column|null $column */
            $column = count($columnAttributes) > 0 ? $columnAttributes[0]->newInstance() : null;
            $type = $column?->type ?? $this->inferType($property);
            $mapping[$property->getName()] = [
                'name' => $id ? 'id' : ($column?->name ?? $this->snakeCase($property->getName())),
                'type' => $id ? 'integer' : $type,
                'nullable' => $id || ($column?->nullable ?? false),
                'length' => $column?->length,
                'unique' => $column?->unique ?? false,
                'default' => $column?->default,
                'id' => $id,
            ];
        }

        return $mapping;
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, array{name: string, type: string, nullable: bool, length: int|null, unique: bool, default: mixed, id: bool}> $mapping
     * @return array<string, mixed>
     */
    private function mapCriteria(array $criteria, array $mapping): array
    {
        $mapped = [];
        foreach ($criteria as $property => $value) {
            if (!isset($mapping[$property])) {
                throw new \InvalidArgumentException(sprintf('Unknown persisted property "%s".', $property));
            }
            $mapped[$mapping[$property]['name']] = $value;
        }
        return $mapped;
    }

    /**
     * @param array<string, string> $orderBy
     * @param array<string, array{name: string, type: string, nullable: bool, length: int|null, unique: bool, default: mixed, id: bool}> $mapping
     * @return array<string, string>
     */
    private function mapOrderBy(array $orderBy, array $mapping): array
    {
        $mapped = [];
        foreach ($orderBy as $property => $direction) {
            if (!isset($mapping[$property])) {
                throw new \InvalidArgumentException(sprintf('Unknown persisted property "%s".', $property));
            }
            $mapped[$mapping[$property]['name']] = $direction;
        }
        return $mapped;
    }

    /**
     * @param array<string, array{name: string, type: string, nullable: bool, length: int|null, unique: bool, default: mixed, id: bool}> $mapping
     * @param array<string, mixed> $row
     */
    private function hydrate(ReflectionClass $class, array $mapping, array $row): object
    {
        $entity = $class->newInstanceWithoutConstructor();
        foreach ($mapping as $propertyName => $metadata) {
            if (!array_key_exists($metadata['name'], $row)) {
                continue;
            }
            $property = $class->getProperty($propertyName);
            $property->setValue($entity, $this->fromStorageValue($row[$metadata['name']], $metadata['type']));
        }
        return $entity;
    }

    private function toStorageValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }
        return match ($type) {
            'boolean' => $value ? 1 : 0,
            'datetime' => $value instanceof \DateTimeInterface ? $value->format(\DateTimeInterface::ATOM) : $value,
            'json' => json_encode($value, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }

    private function fromStorageValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }
        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => (bool) $value,
            'datetime' => new \DateTimeImmutable((string) $value),
            'json' => json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }

    private function inferType(ReflectionProperty $property): string
    {
        $type = $property->getType();
        $name = $type instanceof \ReflectionNamedType ? $type->getName() : 'string';
        return match ($name) {
            'int' => 'integer',
            'float' => 'float',
            'bool' => 'boolean',
            'array' => 'json',
            \DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class => 'datetime',
            default => 'string',
        };
    }

    private function snakeCase(string $name): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    private function idPropertyFor(ReflectionClass $class): ReflectionProperty
    {
        $idProperty = null;
        foreach ($class->getProperties() as $property) {
            if (count($property->getAttributes(Id::class)) === 0) {
                continue;
            }
            if ($idProperty !== null) {
                throw new \RuntimeException(sprintf(
                    'Entity %s has multiple #[Id] properties; exactly one is required.',
                    $class->getName()
                ));
            }
            $idProperty = $property;
        }
        if ($idProperty === null) {
            throw new \RuntimeException(sprintf(
                'Entity %s has no #[Id] property; mark the primary key field with #[Id].',
                $class->getName()
            ));
        }
        return $idProperty;
    }

    private function entityNameFor(object $entity): string
    {
        return $this->nameFor($entity::class);
    }

    private function nameFor(string $entityClass): string
    {
        $reflection = new ReflectionClass($entityClass);
        $attributes = $reflection->getAttributes(Entity::class);
        if (count($attributes) === 0) {
            throw new \RuntimeException(sprintf('%s is not marked with #[Entity]', $entityClass));
        }

        /** @var Entity $entity */
        $entity = $attributes[0]->newInstance();
        if (!$this->discoverer->discover()->has($entity->name)) {
            throw new \RuntimeException(sprintf(
                'Entity "%s" not discovered — check the file path under src/Feature/*/Entity/',
                $entity->name
            ));
        }

        return $entity->table ?? $entity->name;
    }
}
