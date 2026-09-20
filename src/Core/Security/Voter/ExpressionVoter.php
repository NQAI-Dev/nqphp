<?php

declare(strict_types=1);

namespace Nqphp\Core\Security\Voter;

use Nqphp\Core\Security\UserInterface;

/**
 * Voter evaluating security attributes via registered expression callbacks.
 * Allows defining custom inline authorization rules cleanly.
 */
class ExpressionVoter extends AbstractVoter
{
    /**
     * @var array<string, callable(UserInterface|null, mixed): bool>
     */
    private array $expressions = [];

    /**
     * Register a callback evaluator for an attribute string.
     *
     * @param string $attribute
     * @param callable(UserInterface|null, mixed): bool $evaluator
     */
    public function register(string $attribute, callable $evaluator): self
    {
        $this->expressions[$attribute] = $evaluator;
        return $this;
    }

    public function supports(string $attribute, mixed $subject): bool
    {
        return isset($this->expressions[$attribute]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, ?UserInterface $user): bool
    {
        $evaluator = $this->expressions[$attribute];
        return (bool) $evaluator($user, $subject);
    }
}
