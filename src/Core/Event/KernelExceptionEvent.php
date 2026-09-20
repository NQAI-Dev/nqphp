<?php

declare(strict_types=1);

namespace Nqphp\Core\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Dispatched by the Kernel whenever an uncaught exception or error is thrown
 * during HTTP request handling.
 *
 * A listener may convert the exception into a user-friendly Response
 * (JSON, HTML error page, metrics) by calling setResponse().
 */
final class KernelExceptionEvent extends Event
{
    private ?Response $response = null;

    public function __construct(
        private readonly Request $request,
        private readonly Throwable $throwable
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }

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
