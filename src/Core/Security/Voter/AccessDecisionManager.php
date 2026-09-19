<?php

declare(strict_types=1);

namespace Nqphp\Core\Security\Voter;

use Nqphp\Core\Security\UserInterface;

/**
 * Orchestrates registered voters to decide authorization.
 * Defaults to affirmative strategy (access is granted if at least one voter grants).
 */
class AccessDecisionManager
{
    public const STRATEGY_AFFIRMATIVE = 'affirmative';
    public const STRATEGY_UNANIMOUS = 'unanimous';
    public const STRATEGY_CONSENSUS = 'consensus';

    /**
     * @param iterable<VoterInterface> $voters
     * @param string $strategy One of affirmative, unanimous, consensus
     * @param bool $allowIfAllAbstain Access decision if all voters abstain
     */
    public function __construct(
        private readonly iterable $voters = [],
        private readonly string $strategy = self::STRATEGY_AFFIRMATIVE,
        private readonly bool $allowIfAllAbstain = false,
    ) {
    }

    public function decide(?UserInterface $user, string $attribute, mixed $subject = null): bool
    {
        return match ($this->strategy) {
            self::STRATEGY_UNANIMOUS => $this->decideUnanimous($user, $attribute, $subject),
            self::STRATEGY_CONSENSUS => $this->decideConsensus($user, $attribute, $subject),
            default => $this->decideAffirmative($user, $attribute, $subject),
        };
    }

    private function decideAffirmative(?UserInterface $user, string $attribute, mixed $subject): bool
    {
        $hasVoted = false;

        foreach ($this->voters as $voter) {
            $result = $voter->vote($user, $attribute, $subject);

            if ($result === VoterInterface::ACCESS_GRANTED) {
                return true;
            }

            if ($result === VoterInterface::ACCESS_DENIED) {
                $hasVoted = true;
            }
        }

        return $hasVoted ? false : $this->allowIfAllAbstain;
    }

    private function decideUnanimous(?UserInterface $user, string $attribute, mixed $subject): bool
    {
        $grantCount = 0;

        foreach ($this->voters as $voter) {
            $result = $voter->vote($user, $attribute, $subject);

            if ($result === VoterInterface::ACCESS_DENIED) {
                return false;
            }

            if ($result === VoterInterface::ACCESS_GRANTED) {
                $grantCount++;
            }
        }

        return $grantCount > 0 || $this->allowIfAllAbstain;
    }

    private function decideConsensus(?UserInterface $user, string $attribute, mixed $subject): bool
    {
        $grantCount = 0;
        $denyCount = 0;

        foreach ($this->voters as $voter) {
            $result = $voter->vote($user, $attribute, $subject);

            if ($result === VoterInterface::ACCESS_GRANTED) {
                $grantCount++;
            } elseif ($result === VoterInterface::ACCESS_DENIED) {
                $denyCount++;
            }
        }

        if ($grantCount > $denyCount) {
            return true;
        }

        if ($denyCount > $grantCount) {
            return false;
        }

        return $grantCount > 0 ? true : $this->allowIfAllAbstain;
    }
}
