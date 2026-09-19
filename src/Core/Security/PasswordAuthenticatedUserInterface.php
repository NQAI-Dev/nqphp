<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

/**
 * Marker interface for users whose credentials include a hashed password.
 */
interface PasswordAuthenticatedUserInterface extends UserInterface
{
    /**
     * Returns the hashed password used to authenticate the user.
     */
    public function getPassword(): ?string;
}
