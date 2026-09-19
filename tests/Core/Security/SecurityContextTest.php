<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security;

use Nqphp\Core\Security\SecurityContext;
use Nqphp\Core\Security\User;
use Nqphp\Core\Session\ArraySession;
use PHPUnit\Framework\TestCase;

class SecurityContextTest extends TestCase
{
    public function testUnauthenticatedState(): void
    {
        $context = new SecurityContext();
        $this->assertFalse($context->isAuthenticated());
        $this->assertNull($context->getUser());
        $this->assertFalse($context->isGranted('ROLE_ADMIN'));
    }

    public function testSetUserAndRoles(): void
    {
        $context = new SecurityContext();
        $user = new User('alice@example.com', ['ROLE_ADMIN']);

        $context->setUser($user);

        $this->assertTrue($context->isAuthenticated());
        $this->assertSame($user, $context->getUser());
        $this->assertSame('alice@example.com', $context->getUser()->getUserIdentifier());
        $this->assertTrue($context->isGranted('ROLE_ADMIN'));
        $this->assertTrue($context->isGranted('ROLE_USER'));
        $this->assertFalse($context->isGranted('ROLE_SUPERADMIN'));
    }

    public function testStatePersistenceInSession(): void
    {
        $session = new ArraySession();
        $session->start();

        $context1 = new SecurityContext($session);
        $user = new User('bob', ['ROLE_EDITOR']);
        $context1->setUser($user);

        $context2 = new SecurityContext($session);
        $this->assertTrue($context2->isAuthenticated());
        $this->assertNotNull($context2->getUser());
        $this->assertSame('bob', $context2->getUser()->getUserIdentifier());
        $this->assertTrue($context2->isGranted('ROLE_EDITOR'));

        $context2->setUser(null);
        $this->assertFalse($context2->isAuthenticated());
        $this->assertFalse($session->has(SecurityContext::SESSION_KEY));
    }
}
