<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contract for CSRF token managers in nqphp.
 */
interface CsrfTokenManagerInterface
{
    public function getToken(Request $request): ?string;

    public function buildCookie(Request $request): Cookie;

    public function isValid(Request $request): bool;

    public function isStateChanging(Request $request): bool;
}
