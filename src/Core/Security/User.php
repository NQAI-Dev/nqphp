<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

/**
 * Basic immutable User implementation of UserInterface.
 */
final class User implements UserInterface
{
    /**
     * @param string $identifier Unique username/email/id
     * @param string[] $roles List of assigned roles
     */
    public function __construct(
        private readonly string $identifier,
        private readonly array $roles = ['ROLE_USER'],
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_values(array_unique($roles));
    }
}
