<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

interface SecurityContextInterface
{
    public function getUser(): ?UserInterface;

    public function setUser(?UserInterface $user): void;

    public function isAuthenticated(): bool;

    public function isGranted(string $role): bool;
}
