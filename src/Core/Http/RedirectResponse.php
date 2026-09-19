<?php

declare(strict_types=1);

namespace Nqphp\Core\Http;

use Nqphp\Core\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Fluent redirect response supporting flash messages and route redirect chaining.
 */
class RedirectResponse extends SymfonyRedirectResponse
{
    private ?SessionInterface $session = null;

    /**
     * Associate a session instance to allow withFlash/withErrors chaining.
     */
    public function withSession(SessionInterface $session): self
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Add a flash message to the associated session before redirecting.
     */
    public function withFlash(string $key, mixed $message): self
    {
        if ($this->session !== null) {
            $this->session->addFlash($key, $message);
        }
        return $this;
    }

    /**
     * Convenient shortcut for flashing an error message.
     */
    public function withError(string $message): self
    {
        return $this->withFlash('error', $message);
    }

    /**
     * Convenient shortcut for flashing a success message.
     */
    public function withSuccess(string $message): self
    {
        return $this->withFlash('success', $message);
    }
}
