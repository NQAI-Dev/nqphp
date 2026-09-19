<?php

declare(strict_types=1);

namespace Nqphp\Core\Security;

use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Middleware\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware protecting state-changing HTTP requests against CSRF attacks.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(?CsrfTokenManagerInterface $csrfTokenManager = null)
    {
        $this->csrfTokenManager = $csrfTokenManager ?? new CsrfTokenManager();
    }

    public function process(Request $request, callable $next): Response
    {
        if ($this->csrfTokenManager->isStateChanging($request) && !$this->csrfTokenManager->isValid($request)) {
            throw new HttpException(403, 'Invalid CSRF token.');
        }

        /** @var Response $response */
        $response = $next($request);

        if (!$request->cookies->has(CsrfTokenManager::COOKIE_NAME)) {
            $response->headers->setCookie($this->csrfTokenManager->buildCookie($request));
        }

        return $response;
    }
}
