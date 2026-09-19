<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

use InvalidArgumentException;

/**
 * Default password hasher implementation wrapping PHP's native password_* functions.
 * Defaults to PASSWORD_BCRYPT or PASSWORD_ARGON2ID if available.
 */
class NativePasswordHasher implements PasswordHasherInterface
{
    /**
     * @param string|int $algo Target algorithm (e.g. PASSWORD_BCRYPT, PASSWORD_ARGON2ID, or "2y")
     * @param array<string, mixed> $options Algorithm-specific options (e.g. ['cost' => 12])
     */
    public function __construct(
        private readonly string|int $algo = PASSWORD_DEFAULT,
        private readonly array $options = []
    ) {
    }

    public function hash(string $plainPassword): string
    {
        if ($plainPassword === '') {
            throw new InvalidArgumentException('Password cannot be empty.');
        }

        $hash = password_hash($plainPassword, $this->algo, $this->options);

        if ($hash === false) {
            throw new \RuntimeException('Failed to hash password.');
        }

        return $hash;
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        if ($plainPassword === '' || $hash === '') {
            return false;
        }

        return password_verify($plainPassword, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algo, $this->options);
    }
}
