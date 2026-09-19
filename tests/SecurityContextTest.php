<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Security\SecurityContext;
use Nqphp\Core\Security\SecurityContextInterface;
use Nqphp\Core\Security\UserInterface;
use Nqphp\Core\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class DummyUser implements UserInterface
{
    public function __construct(
        private readonly string $username,
        private readonly array $roles = ['ROLE_USER']
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}

class SecurityContextTest extends TestCase
{
    public function testAnonymousByDefault(): void
    {
        $context = new SecurityContext();
        $this->assertNull($context->getUser());
        $this->assertFalse($context->isAuthenticated());
        $this->assertFalse($context->isGranted('ROLE_ADMIN'));
    }

    public function testSetUserAndCheckRoles(): void
    {
        $context = new SecurityContext();
        $user = new DummyUser('alice', ['ROLE_USER', 'ROLE_ADMIN']);

        $context->setUser($user);

        $this->assertSame($user, $context->getUser());
        $this->assertTrue($context->isAuthenticated());
        $this->assertTrue($context->isGranted('ROLE_ADMIN'));
        $this->assertTrue($context->isGranted('ROLE_USER'));
        $this->assertFalse($context->isGranted('ROLE_SUPERADMIN'));
    }

    public function testPersistsInSession(): void
    {
        $session = new SessionManager();
        $context1 = new SecurityContext($session);
        $user = new DummyUser('bob', ['ROLE_EDITOR']);

        $context1->setUser($user);

        // В контексте2 над тем же session manager юзер восстанавливается
        $context2 = new SecurityContext($session);
        $this->assertTrue($context2->isAuthenticated());
        $this->assertSame('bob', $context2->getUser()?->getUserIdentifier());
        $this->assertTrue($context2->isGranted('ROLE_EDITOR'));

        // Logout
        $context2->setUser(null);
        $this->assertFalse($context2->isAuthenticated());
        $this->assertFalse($session->has(SecurityContext::SESSION_KEY));
    }

    public function testKernelExposesSecurityContext(): void
    {
        $kernel = new Kernel(__DIR__ . '/fixtures/app');
        $this->assertInstanceOf(SecurityContextInterface::class, $kernel->security());
    }
}
