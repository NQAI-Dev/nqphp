<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Nqphp\Core\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatically starts session, attaches it to the request attributes,
 * and attaches session cookie to the outgoing response if applicable.
 */
class SessionMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE_SESSION = '_session';

    public function __construct(
        private readonly SessionInterface $session,
        private readonly string $cookieName = 'nqphp_session',
        private readonly int $cookieLifetime = 0,
        private readonly string $cookiePath = '/',
        private readonly ?string $cookieDomain = null,
        private readonly bool $cookieSecure = false,
        private readonly bool $cookieHttpOnly = true,
        private readonly string $cookieSameSite = Cookie::SAMESITE_LAX
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $request->attributes->set(self::ATTRIBUTE_SESSION, $this->session);

        /** @var Response $response */
        $response = $next($request);

        // Attach session cookie if session ID is accessible or configured
        if ($this->session->isStarted()) {
            $sessionId = session_id();
            if (!empty($sessionId)) {
                $cookie = Cookie::create(
                    name: $this->cookieName,
                    value: $sessionId,
                    expire: $this->cookieLifetime > 0 ? time() + $this->cookieLifetime : 0,
                    path: $this->cookiePath,
                    domain: $this->cookieDomain,
                    secure: $this->cookieSecure,
                    httpOnly: $this->cookieHttpOnly,
                    raw: false,
                    sameSite: $this->cookieSameSite
                );
                $response->headers->setCookie($cookie);
            }
        }

        return $response;
    }
}
