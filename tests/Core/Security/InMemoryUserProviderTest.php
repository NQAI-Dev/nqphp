<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security;

use Nqphp\Core\Security\InMemoryUserProvider;
use Nqphp\Core\Security\User;
use Nqphp\Core\Security\UserInterface;
use Nqphp\Core\Security\UserNotFoundException;
use PHPUnit\Framework\TestCase;

class InMemoryUserProviderTest extends TestCase
{
    public function testLoadsExistingUserCaseInsensitively(): void
    {
        $admin = new User('Admin@example.com', ['ROLE_ADMIN']);
        $provider = new InMemoryUserProvider([$admin]);

        $loaded = $provider->loadUserByIdentifier('admin@example.com');
        $this->assertSame('Admin@example.com', $loaded->getUserIdentifier());
        $this->assertContains('ROLE_ADMIN', $loaded->getRoles());
    }

    public function testThrowsUserNotFoundExceptionWhenMissing(): void
    {
        $provider = new InMemoryUserProvider();

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User with identifier "unknown" was not found.');

        $provider->loadUserByIdentifier('unknown');
    }

    public function testRefreshesUser(): void
    {
        $user = new User('john_doe');
        $provider = new InMemoryUserProvider([$user]);

        $refreshed = $provider->refreshUser($user);
        $this->assertSame('john_doe', $refreshed->getUserIdentifier());
    }

    public function testRefreshThrowsWhenClassUnsupported(): void
    {
        $provider = new InMemoryUserProvider();

        $mockUser = $this->createMock(UserInterface::class);
        $mockUser->method('getUserIdentifier')->willReturn('dummy');

        $this->assertTrue($provider->supportsClass($mockUser::class));
    }

    public function testSupportsClass(): void
    {
        $provider = new InMemoryUserProvider();

        $this->assertTrue($provider->supportsClass(User::class));
        $this->assertTrue($provider->supportsClass(UserInterface::class));
        $this->assertFalse($provider->supportsClass(\stdClass::class));
    }
}
