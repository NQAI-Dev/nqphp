<?php

declare(strict_types=1);

namespace Nqphp\Core\Entity;

use Nqphp\Core\Attribute\Entity;
use ReflectionClass;

/**
 * In-memory entity store. CRUD for #[Entity]-discovered classes.
 *
 * Designed as the LIGHTWEIGHT Phase 2 half — the data layer that
 * apps can use during prototyping or for read-only fixture data.
 * Real persistence (Doctrine, PDO, etc.) lands as a follow-up commit
 * that implements the same interface but stores rows in SQL instead
 * of in-memory arrays.
 *
 * Usage:
 *   $store = new EntityStore($entityDiscoverer);
 *   $user = new User(['email' => 'foo@bar', 'name' => 'Foo']);
 *   $store->save($user);          // assigns $id and stores
 *   $user = $store->find('user', $user->id);
 *   $all = $store->findAll('user');
 *
 * Persistence shape: `array<entityName, array<id, object>>` keyed by
 * the entity's `name` attribute and an auto-assigned `$id` field on
 * the entity. If the entity class has no `id` field, the store will
 * throw on `save` — entities must declare an `int $id` property.
 */
final class EntityStore
{
    /** @var array<string, array<int, object>> name → rows */
    private array $rows = [];

    /** @var array<string, int> name → next id counter */
    private array $nextIds = [];

    public function __construct(private readonly EntityDiscoverer $discoverer)
    {
    }

    /** Persist a new entity (or update if $id is already set). */
    public function save(object $entity): object
    {
        $entityName = $this->entityNameFor($entity);
        $reflection = new ReflectionClass($entity);
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        if ($idProp->getValue($entity) === null) {
            $idProp->setValue($entity, $this->nextIds[$entityName] ??= 1);
        }
        $id = (int) $idProp->getValue($entity);
        $this->rows[$entityName][$id] = $entity;
        return $entity;
    }

    /** @return object|null */
    public function find(string $entityName, int $id): ?object
    {
        return $this->rows[$entityName][$id] ?? null;
    }

    /** @return list<object> */
    public function findAll(string $entityName): array
    {
        return array_values($this->rows[$entityName] ?? []);
    }

    /** Delete by id. Returns true if something was deleted. */
    public function delete(string $entityName, int $id): bool
    {
        if (!isset($this->rows[$entityName][$id])) {
            return false;
        }
        unset($this->rows[$entityName][$id]);
        return true;
    }

    /**
     * Resolve the entity name for a given object via #[Entity].
     * Throws if the object's class isn't discovered.
     */
    private function entityNameFor(object $entity): string
    {
        $reflection = new ReflectionClass($entity);
        $attrs = $reflection->getAttributes(Entity::class);
        if (\count($attrs) === 0) {
            throw new \RuntimeException(sprintf(
                '%s is not marked with #[Entity]',
                $reflection->getName()
            ));
        }
        /** @var Entity $entityAttr */
        $entityAttr = $attrs[0]->newInstance();
        $name = $entityAttr->name;
        if (!$this->discoverer->discover()->has($name)) {
            throw new \RuntimeException(sprintf(
                'Entity "%s" not discovered — check the file path under src/Feature/*/Entity/',
                $name
            ));
        }
        return $name;
    }
}
