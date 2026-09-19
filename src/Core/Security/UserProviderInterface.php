<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

/**
 * Interface for loading and reloading UserInterface instances by identifier.
 */
interface UserProviderInterface
{
    /**
     * Loads a user by their unique identifier (username, email, id, etc.).
     *
     * @throws UserNotFoundException If the user is not found.
     */
    public function loadUserByIdentifier(string $identifier): UserInterface;

    /**
     * Refreshes the user entity for "remember me" or session re-authentication.
     *
     * @throws UserNotFoundException If the user is no longer found.
     */
    public function refreshUser(UserInterface $user): UserInterface;

    /**
     * Whether this provider supports the given user class.
     *
     * @param class-string<UserInterface> $class
     */
    public function supportsClass(string $class): bool;
}
