<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security;

use Nqphp\Core\Security\BadCredentialsException;
use Nqphp\Core\Security\InMemoryUserProvider;
use Nqphp\Core\Security\NativePasswordHasher;
use Nqphp\Core\Security\PasswordAuthenticatedUserInterface;
use Nqphp\Core\Security\SecurityContext;
use Nqphp\Core\Security\User;
use Nqphp\Core\Security\UserAuthenticator;
use Nqphp\Core\Security\UserNotFoundException;
use PHPUnit\Framework\TestCase;

final class TestPasswordUser implements PasswordAuthenticatedUserInterface
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        private readonly string $identifier,
        private readonly string $passwordHash,
        private readonly array $roles = ['ROLE_USER'],
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }
}

class UserAuthenticatorTest extends TestCase
{
    private NativePasswordHasher $hasher;
    private SecurityContext $securityContext;

    protected function setUp(): void
    {
        $this->hasher = new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 4]);
        $this->securityContext = new SecurityContext();
    }

    public function testAuthenticateSuccess(): void
    {
        $hash = $this->hasher->hash('secret123');
        $user = new TestPasswordUser('alice@example.com', $hash, ['ROLE_USER', 'ROLE_ADMIN']);

        $provider = new InMemoryUserProvider([$user]);
        $authenticator = new UserAuthenticator($provider, $this->hasher, $this->securityContext);

        $authenticatedUser = $authenticator->authenticate('alice@example.com', 'secret123');

        $this->assertSame($user, $authenticatedUser);
        $this->assertTrue($this->securityContext->isAuthenticated());
        $this->assertSame($user, $this->securityContext->getUser());
        $this->assertTrue($this->securityContext->isGranted('ROLE_ADMIN'));
    }

    public function testAuthenticateWrongPasswordThrowsBadCredentialsException(): void
    {
        $hash = $this->hasher->hash('secret123');
        $user = new TestPasswordUser('bob@example.com', $hash);

        $provider = new InMemoryUserProvider([$user]);
        $authenticator = new UserAuthenticator($provider, $this->hasher, $this->securityContext);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Invalid password.');

        $authenticator->authenticate('bob@example.com', 'wrongpassword');
    }

    public function testAuthenticateNonExistentUserThrowsUserNotFoundException(): void
    {
        $provider = new InMemoryUserProvider();
        $authenticator = new UserAuthenticator($provider, $this->hasher, $this->securityContext);

        $this->expectException(UserNotFoundException::class);

        $authenticator->authenticate('unknown@example.com', 'secret123');
    }

    public function testAuthenticateUserWithoutPasswordSupportThrowsBadCredentialsException(): void
    {
        $user = new User('charlie@example.com');

        $provider = new InMemoryUserProvider([$user]);
        $authenticator = new UserAuthenticator($provider, $this->hasher, $this->securityContext);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('User does not support password authentication.');

        $authenticator->authenticate('charlie@example.com', 'secret123');
    }
}
