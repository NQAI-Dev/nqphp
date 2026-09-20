<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security\Voter;

use Nqphp\Core\Security\User;
use Nqphp\Core\Security\UserInterface;
use Nqphp\Core\Security\Voter\CallbackVoter;
use Nqphp\Core\Security\Voter\VoterInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

class CallbackVoterTest extends TestCase
{
    public function testAbstainsWhenAttributeNotSupported(): void
    {
        $voter = new CallbackVoter('post.edit', fn () => true);
        $user = new User('alex', ['ROLE_USER']);

        $this->assertFalse($voter->supports('post.delete', null));
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($user, 'post.delete', null)
        );
    }

    public function testAbstainsWhenSubjectClassDoesNotMatch(): void
    {
        $voter = new CallbackVoter('item.view', fn () => true, stdClass::class);
        $user = new User('alex', ['ROLE_USER']);

        $this->assertFalse($voter->supports('item.view', 'not-an-object'));
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($user, 'item.view', 'not-an-object')
        );
    }

    public function testGrantsAccessWhenCallbackReturnsTrue(): void
    {
        $voter = new CallbackVoter('post.edit', function (?UserInterface $user, mixed $subject): bool {
            return $user !== null && isset($subject->author) && $subject->author === $user->getUserIdentifier();
        });

        $user = new User('author_user', ['ROLE_USER']);
        $post = (object) ['author' => 'author_user'];

        $this->assertTrue($voter->supports('post.edit', $post));
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($user, 'post.edit', $post)
        );
    }

    public function testDeniesAccessWhenCallbackReturnsFalse(): void
    {
        $voter = new CallbackVoter('post.edit', function (?UserInterface $user, mixed $subject): bool {
            return $user !== null && isset($subject->author) && $subject->author === $user->getUserIdentifier();
        });

        $user = new User('other_user', ['ROLE_USER']);
        $post = (object) ['author' => 'author_user'];

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($user, 'post.edit', $post)
        );
    }

    public function testSupportsArrayOfAttributesAndExplicitIntResult(): void
    {
        $voter = new CallbackVoter(['article.publish', 'article.archive'], function (?UserInterface $user, mixed $subject, string $attribute): int {
            if ($attribute === 'article.publish') {
                return VoterInterface::ACCESS_GRANTED;
            }
            return VoterInterface::ACCESS_DENIED;
        });

        $user = new User('admin', ['ROLE_ADMIN']);

        $this->assertTrue($voter->supports('article.publish', null));
        $this->assertTrue($voter->supports('article.archive', null));

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($user, 'article.publish', null)
        );
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($user, 'article.archive', null)
        );
    }
}
