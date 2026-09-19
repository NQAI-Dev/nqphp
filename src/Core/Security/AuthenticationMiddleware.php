<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Middleware\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces authentication and optional role requirements on routes.
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @param SecurityContextInterface $security Security context
     * @param string|null $requiredRole If specified, checks $security->isGranted($requiredRole)
     * @param string|null $redirectUrl If set, redirects unauthenticated requests instead of throwing 401
     */
    public function __construct(
        private readonly SecurityContextInterface $security,
        private readonly ?string $requiredRole = null,
        private readonly ?string $redirectUrl = null
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        if (!$this->security->isAuthenticated()) {
            if ($this->redirectUrl !== null) {
                return new Response('', 302, ['Location' => $this->redirectUrl]);
            }
            throw new HttpException(401, 'Authentication required.');
        }

        if ($this->requiredRole !== null && !$this->security->isGranted($this->requiredRole)) {
            throw new HttpException(403, 'Access denied.');
        }

        return $next($request);
    }
}
