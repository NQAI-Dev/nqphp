<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security;

use Nqphp\Core\Security\User;
use Nqphp\Core\Security\Voter\RoleHierarchyVoter;
use Nqphp\Core\Security\Voter\VoterInterface;
use PHPUnit\Framework\TestCase;

class RoleHierarchyVoterTest extends TestCase
{
    private RoleHierarchyVoter $voter;

    protected function setUp(): void
    {
        $hierarchy = [
            'ROLE_ADMIN' => ['ROLE_EDITOR'],
            'ROLE_EDITOR' => ['ROLE_USER'],
            'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_AUDITOR'],
        ];

        $this->voter = new RoleHierarchyVoter($hierarchy);
    }

    public function testSupportsOnlyRoleAttributes(): void
    {
        $this->assertTrue($this->voter->supports('ROLE_ADMIN', null));
        $this->assertTrue($this->voter->supports('ROLE_USER', null));
        $this->assertFalse($this->voter->supports('EDIT_POST', null));
        $this->assertFalse($this->voter->supports('VIEW', null));
    }

    public function testAccessDeniedForAnonymousUser(): void
    {
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote(null, 'ROLE_USER', null)
        );
    }

    public function testDirectRoleGrant(): void
    {
        $user = new User('alice', ['ROLE_USER']);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($user, 'ROLE_USER', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($user, 'ROLE_ADMIN', null)
        );
    }

    public function testTransitiveInheritance(): void
    {
        $admin = new User('bob', ['ROLE_ADMIN']);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($admin, 'ROLE_ADMIN', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($admin, 'ROLE_EDITOR', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($admin, 'ROLE_USER', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($admin, 'ROLE_SUPER_ADMIN', null)
        );
    }

    public function testSuperAdminMultipleBranches(): void
    {
        $super = new User('root', ['ROLE_SUPER_ADMIN']);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($super, 'ROLE_SUPER_ADMIN', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($super, 'ROLE_ADMIN', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($super, 'ROLE_EDITOR', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($super, 'ROLE_USER', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($super, 'ROLE_AUDITOR', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($super, 'ROLE_OTHER', null)
        );
    }

    public function testReachableRolesDeduplicated(): void
    {
        $roles = $this->voter->getReachableRolesForUser(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']);
        $expected = ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_EDITOR', 'ROLE_USER', 'ROLE_AUDITOR'];

        sort($roles);
        sort($expected);

        $this->assertSame($expected, $roles);
    }
}
