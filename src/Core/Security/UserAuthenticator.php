<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

/**
 * Service for authenticating users using identifier and password.
 */
class UserAuthenticator
{
    public function __construct(
        private readonly UserProviderInterface $userProvider,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly SecurityContextInterface $securityContext,
    ) {
    }

    /**
     * Authenticates a user with identifier and plain password.
     * Sets the authenticated user in SecurityContext upon success.
     *
     * @throws UserNotFoundException If user does not exist.
     * @throws BadCredentialsException If password does not match.
     */
    public function authenticate(string $identifier, string $plainPassword): UserInterface
    {
        $user = $this->userProvider->loadUserByIdentifier($identifier);

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw new BadCredentialsException('User does not support password authentication.');
        }

        $hashedPassword = $user->getPassword();
        if ($hashedPassword === null || !$this->passwordHasher->verify($plainPassword, $hashedPassword)) {
            throw new BadCredentialsException('Invalid password.');
        }

        $this->securityContext->setUser($user);

        return $user;
    }
}
