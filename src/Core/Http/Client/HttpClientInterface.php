<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

interface HttpClientInterface
{
    /**
     * Send an HTTP request.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS)
     * @param string $url Target URL
     * @param array{
     *     headers?: array<string, string>,
     *     query?: array<string, mixed>,
     *     json?: mixed,
     *     body?: string,
     *     timeout?: float|int,
     *     max_redirects?: int,
     *     bearer_token?: string,
     *     basic_auth?: array{0: string, 1: string}
     * } $options Request configuration options
     */
    public function request(string $method, string $url, array $options = []): Response;

    public function get(string $url, array $options = []): Response;

    public function post(string $url, array $options = []): Response;

    public function put(string $url, array $options = []): Response;

    public function patch(string $url, array $options = []): Response;

    public function delete(string $url, array $options = []): Response;
}
