<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

/**
 * Composite UserProvider delegating user retrieval to multiple providers in order.
 */
class ChainUserProvider implements UserProviderInterface
{
    /** @var UserProviderInterface[] */
    private array $providers;

    /**
     * @param iterable<UserProviderInterface> $providers
     */
    public function __construct(iterable $providers = [])
    {
        $this->providers = is_array($providers) ? $providers : iterator_to_array($providers);
    }

    public function addProvider(UserProviderInterface $provider): self
    {
        $this->providers[] = $provider;
        return $this;
    }

    /**
     * @return UserProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        foreach ($this->providers as $provider) {
            try {
                return $provider->loadUserByIdentifier($identifier);
            } catch (UserNotFoundException) {
                // Continue to the next provider
            }
        }

        throw new UserNotFoundException(sprintf('User "%s" not found in any registered provider.', $identifier));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsClass(get_class($user))) {
                return $provider->refreshUser($user);
            }
        }

        throw new UserNotFoundException(sprintf('No provider supports refreshing user of class "%s".', get_class($user)));
    }

    public function supportsClass(string $class): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsClass($class)) {
                return true;
            }
        }

        return false;
    }
}
