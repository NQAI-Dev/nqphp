<?php

declare(strict_types=1);

namespace Nqphp\Core\Security\Voter;

use Nqphp\Core\Security\UserInterface;

/**
 * Convenient abstract base class for voters.
 */
abstract class AbstractVoter implements VoterInterface
{
    public function vote(?UserInterface $user, string $attribute, mixed $subject): int
    {
        if (!$this->supports($attribute, $subject)) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->voteOnAttribute($attribute, $subject, $user)
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    /**
     * Perform the actual authorization logic.
     */
    abstract protected function voteOnAttribute(string $attribute, mixed $subject, ?UserInterface $user): bool;
}
