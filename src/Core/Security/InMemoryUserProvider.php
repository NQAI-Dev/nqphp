<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

use InvalidArgumentException;

/**
 * In-memory provider holding static array of users.
 */
class InMemoryUserProvider implements UserProviderInterface
{
    /**
     * @var array<string, UserInterface> Map of normalized identifier => UserInterface
     */
    private array $users = [];

    /**
     * @param iterable<UserInterface> $users
     */
    public function __construct(iterable $users = [])
    {
        foreach ($users as $user) {
            $this->addUser($user);
        }
    }

    public function addUser(UserInterface $user): void
    {
        $key = strtolower($user->getUserIdentifier());
        $this->users[$key] = $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $key = strtolower($identifier);
        if (!isset($this->users[$key])) {
            throw new UserNotFoundException($identifier);
        }

        return $this->users[$key];
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$this->supportsClass($user::class)) {
            throw new InvalidArgumentException(sprintf('Unsupported user class: %s', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return is_subclass_of($class, UserInterface::class) || $class === UserInterface::class;
    }
}
