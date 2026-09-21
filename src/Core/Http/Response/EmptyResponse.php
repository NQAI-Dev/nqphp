<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response with no body (defaults to 204 No Content, supports 200, 201, 304, etc.).
 */
class EmptyResponse extends Response
{
    /**
     * @param int $status HTTP status code (defaults to 204)
     * @param array<string, string|string[]> $headers
     */
    public function __construct(int $status = 204, array $headers = [])
    {
        parent::__construct('', $status, $headers);
    }
}
