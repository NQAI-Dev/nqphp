<?php

declare(strict_types=1);

namespace Nqphp\Core\Security\Voter;

use Nqphp\Core\Security\UserInterface;

/**
 * Votes on ROLE_* attributes using a configurable role inheritance hierarchy.
 * E.g., ['ROLE_ADMIN' => ['ROLE_USER'], 'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN']]
 */
class RoleHierarchyVoter implements VoterInterface
{
    /**
     * @var array<string, list<string>> Computed reachable roles for each defined role
     */
    private array $reachableRoles = [];

    /**
     * @param array<string, list<string>> $hierarchy Map of parent role => list of child roles
     */
    public function __construct(array $hierarchy = [])
    {
        $this->buildReachableRoles($hierarchy);
    }

    public function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'ROLE_');
    }

    public function vote(?UserInterface $user, string $attribute, mixed $subject): int
    {
        if ($user === null) {
            return VoterInterface::ACCESS_DENIED;
        }

        $userRoles = $user->getRoles();
        $allReachable = $this->getReachableRolesForUser($userRoles);

        if (in_array($attribute, $allReachable, true)) {
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_DENIED;
    }

    /**
     * @param list<string> $roles
     * @return list<string>
     */
    public function getReachableRolesForUser(array $roles): array
    {
        $reachable = [];
        foreach ($roles as $role) {
            $reachable[] = $role;
            if (isset($this->reachableRoles[$role])) {
                foreach ($this->reachableRoles[$role] as $childRole) {
                    $reachable[] = $childRole;
                }
            }
        }

        return array_values(array_unique($reachable));
    }

    /**
     * @param array<string, list<string>> $hierarchy
     */
    private function buildReachableRoles(array $hierarchy): void
    {
        foreach ($hierarchy as $parent => $children) {
            $visited = [];
            $this->resolveChildren($parent, $hierarchy, $visited);
            $this->reachableRoles[$parent] = array_values(array_unique($visited));
        }
    }

    /**
     * @param array<string, list<string>> $hierarchy
     * @param list<string> $visited
     */
    private function resolveChildren(string $role, array $hierarchy, array &$visited): void
    {
        if (!isset($hierarchy[$role])) {
            return;
        }

        foreach ($hierarchy[$role] as $child) {
            if (!in_array($child, $visited, true)) {
                $visited[] = $child;
                $this->resolveChildren($child, $hierarchy, $visited);
            }
        }
    }
}
