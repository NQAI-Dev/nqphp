<?php

declare(strict_types=1);

namespace Nqphp\Core\Security\Voter;

use Nqphp\Core\Security\UserInterface;

/**
 * Interface for security voters handling fine-grained permission checks.
 */
interface VoterInterface
{
    public const ACCESS_GRANTED = 1;
    public const ACCESS_ABSTAIN = 0;
    public const ACCESS_DENIED = -1;

    /**
     * Checks if the voter supports the given attribute and subject.
     */
    public function supports(string $attribute, mixed $subject): bool;

    /**
     * Votes on whether to grant access.
     * Must return ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     */
    public function vote(?UserInterface $user, string $attribute, mixed $subject): int;
}
