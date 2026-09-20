<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security;

use Nqphp\Core\Security\ChainUserProvider;
use Nqphp\Core\Security\InMemoryUserProvider;
use Nqphp\Core\Security\User;
use Nqphp\Core\Security\UserInterface;
use Nqphp\Core\Security\UserNotFoundException;
use PHPUnit\Framework\TestCase;

class ChainUserProviderTest extends TestCase
{
    public function testLoadUserByIdentifierFindsUserInFirstProvider(): void
    {
        $user1 = new User('alice', ['ROLE_USER']);
        $p1 = new InMemoryUserProvider(['alice' => $user1]);
        $p2 = new InMemoryUserProvider([]);

        $chain = new ChainUserProvider([$p1, $p2]);

        $loaded = $chain->loadUserByIdentifier('alice');
        $this->assertSame($user1, $loaded);
    }

    public function testLoadUserByIdentifierFallsBackToSecondProvider(): void
    {
        $user2 = new User('bob', ['ROLE_ADMIN']);
        $p1 = new InMemoryUserProvider([]);
        $p2 = new InMemoryUserProvider(['bob' => $user2]);

        $chain = new ChainUserProvider();
        $chain->addProvider($p1);
        $chain->addProvider($p2);

        $loaded = $chain->loadUserByIdentifier('bob');
        $this->assertSame($user2, $loaded);
        $this->assertCount(2, $chain->getProviders());
    }

    public function testLoadUserByIdentifierThrowsWhenNotFoundInAny(): void
    {
        $p1 = new InMemoryUserProvider([]);
        $p2 = new InMemoryUserProvider([]);

        $chain = new ChainUserProvider([$p1, $p2]);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User "charlie" not found in any registered provider.');

        $chain->loadUserByIdentifier('charlie');
    }

    public function testRefreshUserDelegatesToSupportingProvider(): void
    {
        $user = new User('alice', ['ROLE_USER']);
        $p = new InMemoryUserProvider(['alice' => $user]);

        $chain = new ChainUserProvider([$p]);

        $refreshed = $chain->refreshUser($user);
        $this->assertSame($user, $refreshed);
    }

    public function testSupportsClassReturnsTrueIfAnySupports(): void
    {
        $p = new InMemoryUserProvider([]);
        $chain = new ChainUserProvider([$p]);

        $this->assertTrue($chain->supportsClass(User::class));
        $this->assertFalse($chain->supportsClass('NonExistentClass'));
    }
}
