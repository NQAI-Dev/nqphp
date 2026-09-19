<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

/**
 * Represents an authenticated identity in the security context.
 */
interface UserInterface
{
    /**
     * Unique identifier for this user (e.g. user ID, email, or username).
     */
    public function getUserIdentifier(): string;

    /**
     * List of roles granted to the user (e.g. ['ROLE_USER', 'ROLE_ADMIN']).
     *
     * @return string[]
     */
    public function getRoles(): array;
}
