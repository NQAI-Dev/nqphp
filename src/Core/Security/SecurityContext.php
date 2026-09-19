<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

use Nqphp\Core\Session\SessionInterface;

/**
 * Default stateful security context backed by SessionInterface.
 */
class SecurityContext implements SecurityContextInterface
{
    public const SESSION_KEY = '_nqphp_security_user';

    private ?UserInterface $user = null;
    private bool $loaded = false;

    public function __construct(private readonly ?SessionInterface $session = null)
    {
    }

    private function loadUserIfNeeded(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        if ($this->session !== null && $this->session->isStarted() && $this->session->has(self::SESSION_KEY)) {
            $user = $this->session->get(self::SESSION_KEY);
            if ($user instanceof UserInterface) {
                $this->user = $user;
            }
        }
    }

    public function getUser(): ?UserInterface
    {
        $this->loadUserIfNeeded();
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->loaded = true;
        $this->user = $user;
        if ($this->session !== null) {
            if ($user === null) {
                $this->session->remove(self::SESSION_KEY);
            } else {
                $this->session->set(self::SESSION_KEY, $user);
            }
        }
    }

    public function isAuthenticated(): bool
    {
        $this->loadUserIfNeeded();
        return $this->user !== null;
    }

    public function isGranted(string $role): bool
    {
        $this->loadUserIfNeeded();
        if ($this->user === null) {
            return false;
        }

        return in_array($role, $this->user->getRoles(), true);
    }
}
