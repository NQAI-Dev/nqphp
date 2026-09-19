<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dispatched by the Kernel just before a user-facing response is
 * returned (middleware short-circuit, 404, controller result —
 * framework-internal /_nqphp/... responses excluded).
 *
 * Listeners may mutate the response in place (headers, content) or
 * replace it entirely via setResponse().
 */
final class KernelResponseEvent extends Event
{
    public function __construct(
        private readonly Request $request,
        private Response $response,
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    /** Replace the response that will be returned to the client. */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
