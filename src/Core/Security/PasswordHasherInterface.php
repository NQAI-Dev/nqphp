<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

/**
 * Interface for secure password hashing and verification.
 */
interface PasswordHasherInterface
{
    /**
     * Hashes the given plain-text password.
     */
    public function hash(string $plainPassword): string;

    /**
     * Verifies that the plain-text password matches the given hash.
     */
    public function verify(string $plainPassword, string $hash): bool;

    /**
     * Checks if the given hash needs to be rehashed based on current options/algorithm.
     */
    public function needsRehash(string $hash): bool;
}
