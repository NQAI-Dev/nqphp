<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security\Voter;

use Nqphp\Core\Security\User;
use Nqphp\Core\Security\UserInterface;
use Nqphp\Core\Security\Voter\ExpressionVoter;
use Nqphp\Core\Security\Voter\VoterInterface;
use PHPUnit\Framework\TestCase;

class ExpressionVoterTest extends TestCase
{
    public function testAbstainsOnUnregisteredAttribute(): void
    {
        $voter = new ExpressionVoter();
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote(null, 'UNKNOWN_ATTR', null));
    }

    public function testGrantsAccessWhenExpressionReturnsTrue(): void
    {
        $voter = new ExpressionVoter();
        $voter->register('IS_AUTHOR', function (?UserInterface $user, mixed $subject): bool {
            return $user !== null && is_array($subject) && ($subject['author_id'] ?? null) === $user->getUserIdentifier();
        });

        $user = new User('alice-1', ['ROLE_USER']);
        $post = ['id' => 101, 'author_id' => 'alice-1'];

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($user, 'IS_AUTHOR', $post));
    }

    public function testDeniesAccessWhenExpressionReturnsFalse(): void
    {
        $voter = new ExpressionVoter();
        $voter->register('IS_AUTHOR', function (?UserInterface $user, mixed $subject): bool {
            return $user !== null && is_array($subject) && ($subject['author_id'] ?? null) === $user->getUserIdentifier();
        });

        $user = new User('bob-2', ['ROLE_USER']);
        $post = ['id' => 101, 'author_id' => 'alice-1'];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($user, 'IS_AUTHOR', $post));
    }

    public function testWorksWithNullUser(): void
    {
        $voter = new ExpressionVoter();
        $voter->register('PUBLIC_READ', function (?UserInterface $user, mixed $subject): bool {
            return true;
        });

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote(null, 'PUBLIC_READ', null));
    }
}
