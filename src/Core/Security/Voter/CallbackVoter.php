<?php

declare(strict_types=1);

namespace Nqphp\Core\Security\Voter;

use Closure;
use Nqphp\Core\Security\UserInterface;

/**
 * Flexible voter delegating permission checks to closures or callbacks.
 * Useful for inline policies, closures in service definitions, or dynamic rules.
 */
class CallbackVoter implements VoterInterface
{
    /**
     * @param string|list<string> $supportedAttributes Attribute or list of attributes this voter handles
     * @param Closure(?UserInterface, mixed, string): (bool|int) $callback Returns true/false or explicit ACCESS_* constant
     */
    public function __construct(
        private readonly string|array $supportedAttributes,
        private readonly Closure $callback,
        private readonly ?string $expectedSubjectClass = null
    ) {
    }

    public function supports(string $attribute, mixed $subject): bool
    {
        $attributes = is_array($this->supportedAttributes)
            ? $this->supportedAttributes
            : [$this->supportedAttributes];

        if (!in_array($attribute, $attributes, true)) {
            return false;
        }

        if ($this->expectedSubjectClass !== null) {
            return is_object($subject) && ($subject instanceof $this->expectedSubjectClass);
        }

        return true;
    }

    public function vote(?UserInterface $user, string $attribute, mixed $subject): int
    {
        if (!$this->supports($attribute, $subject)) {
            return self::ACCESS_ABSTAIN;
        }

        $result = ($this->callback)($user, $subject, $attribute);

        if (is_int($result)) {
            return match ($result) {
                self::ACCESS_GRANTED => self::ACCESS_GRANTED,
                self::ACCESS_DENIED => self::ACCESS_DENIED,
                default => self::ACCESS_ABSTAIN,
            };
        }

        return $result ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }
}
