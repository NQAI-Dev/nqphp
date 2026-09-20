<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * HTTP redirect response with helper methods and defaults.
 */
class RedirectResponse extends SymfonyRedirectResponse
{
    /**
     * @param string $url
     * @param int $status
     * @param array<string, string|string[]> $headers
     */
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        parent::__construct($url, $status, $headers);
    }

    /**
     * Create a permanent 301 redirect.
     *
     * @param string $url
     * @param array<string, string|string[]> $headers
     * @return static
     */
    public static function permanent(string $url, array $headers = []): static
    {
        return new static($url, 301, $headers);
    }

    /**
     * Create a temporary 302 redirect.
     *
     * @param string $url
     * @param array<string, string|string[]> $headers
     * @return static
     */
    public static function temporary(string $url, array $headers = []): static
    {
        return new static($url, 302, $headers);
    }

    /**
     * Create a 303 See Other redirect.
     *
     * @param string $url
     * @param array<string, string|string[]> $headers
     * @return static
     */
    public static function seeOther(string $url, array $headers = []): static
    {
        return new static($url, 303, $headers);
    }
}
