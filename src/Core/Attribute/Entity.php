<?php

declare(strict_types=1);

namespace Nqphp\Core\Attribute;

/**
 * Mark a class as a domain entity (data shape) discoverable by
 * EntityDiscoverer.
 *
 * Entities are auto-discovered from:
 *   - src/Feature/{Name}/Entity/{File}.php   (feature-scoped entities)
 *   - src/Core/Entity/{File}.php             (framework, future)
 *
 * The class is expected to be a POPO with public properties (the
 * column shape). EntityStore reads/writes them via reflection; no
 * Doctrine or DB required. The whole point of this attribute is
 * to give features a typed data layer they can swap out later
 * (swap `EntityStore` impl → Doctrine bridge) without changing
 * entity classes.
 *
 * Example:
 *   #[Entity(name: 'user')]
 *   final class User {
 *       public ?int $id = null;
 *       public string $email;
 *       public string $name;
 *   }
 *
 * The `name` is the canonical handle used by EntityStore CRUD ops.
 * Convention: lowercase singular noun (`user`, `post`, `comment`).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Entity
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
