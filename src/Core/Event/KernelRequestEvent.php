<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dispatched by the Kernel before CSRF checks, middleware and routing.
 *
 * A listener may call setResponse() to short-circuit the pipeline:
 * the kernel then skips everything below (middleware, route hooks,
 * controller) and returns that response directly.
 */
final class KernelRequestEvent extends Event
{
    private ?Response $response = null;

    public function __construct(private readonly Request $request)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /** Short-circuit the request pipeline with this response. */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }
}
