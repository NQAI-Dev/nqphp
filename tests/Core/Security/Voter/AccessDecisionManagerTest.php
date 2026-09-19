<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security\Voter;

use Nqphp\Core\Security\User;
use Nqphp\Core\Security\UserInterface;
use Nqphp\Core\Security\Voter\AbstractVoter;
use Nqphp\Core\Security\Voter\AccessDecisionManager;
use Nqphp\Core\Security\Voter\VoterInterface;
use PHPUnit\Framework\TestCase;

final class PostEditVoter extends AbstractVoter
{
    public function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'POST_EDIT' && is_array($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, ?UserInterface $user): bool
    {
        if ($user === null) {
            return false;
        }

        return ($subject['author_id'] ?? null) === $user->getUserIdentifier()
            || in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}

final class DenyAllVoter implements VoterInterface
{
    public function supports(string $attribute, mixed $subject): bool
    {
        return true;
    }

    public function vote(?UserInterface $user, string $attribute, mixed $subject): int
    {
        return self::ACCESS_DENIED;
    }
}

class AccessDecisionManagerTest extends TestCase
{
    public function testAffirmativeGrantsWhenOneVoterGrants(): void
    {
        $voter = new PostEditVoter();
        $manager = new AccessDecisionManager([$voter], AccessDecisionManager::STRATEGY_AFFIRMATIVE);

        $author = new User('author-1');
        $post = ['author_id' => 'author-1'];

        $this->assertTrue($manager->decide($author, 'POST_EDIT', $post));
    }

    public function testAffirmativeDeniesWhenNoVoterGrants(): void
    {
        $voter = new PostEditVoter();
        $manager = new AccessDecisionManager([$voter], AccessDecisionManager::STRATEGY_AFFIRMATIVE);

        $other = new User('other-user');
        $post = ['author_id' => 'author-1'];

        $this->assertFalse($manager->decide($other, 'POST_EDIT', $post));
    }

    public function testUnanimousDeniesIfAnyDenies(): void
    {
        $voter1 = new PostEditVoter();
        $voter2 = new DenyAllVoter();

        $manager = new AccessDecisionManager([$voter1, $voter2], AccessDecisionManager::STRATEGY_UNANIMOUS);

        $admin = new User('admin', ['ROLE_ADMIN']);
        $post = ['author_id' => 'author-1'];

        $this->assertFalse($manager->decide($admin, 'POST_EDIT', $post));
    }

    public function testConsensusStrategy(): void
    {
        $grantVoter1 = new PostEditVoter();
        $grantVoter2 = new PostEditVoter();
        $denyVoter = new DenyAllVoter();

        $manager = new AccessDecisionManager(
            [$grantVoter1, $grantVoter2, $denyVoter],
            AccessDecisionManager::STRATEGY_CONSENSUS
        );

        $author = new User('author-1');
        $post = ['author_id' => 'author-1'];

        $this->assertTrue($manager->decide($author, 'POST_EDIT', $post));
    }

    public function testAbstainFallback(): void
    {
        $voter = new PostEditVoter();
        $managerDefault = new AccessDecisionManager([$voter], allowIfAllAbstain: false);
        $managerAllow = new AccessDecisionManager([$voter], allowIfAllAbstain: true);

        $this->assertFalse($managerDefault->decide(null, 'UNKNOWN_ATTR', null));
        $this->assertTrue($managerAllow->decide(null, 'UNKNOWN_ATTR', null));
    }
}
