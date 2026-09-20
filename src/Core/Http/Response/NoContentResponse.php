<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP 204 No Content response representation.
 */
class NoContentResponse extends Response
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(array $headers = [])
    {
        parent::__construct('', Response::HTTP_NO_CONTENT, $headers);
    }
}
