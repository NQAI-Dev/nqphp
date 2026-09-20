<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that injects default/global headers (e.g. User-Agent, X-Api-Key, Accept)
 * into every outgoing HTTP request if not already overridden.
 */
class HeaderAppendingHttpClient implements HttpClientInterface
{
    /**
     * @param array<string, string> $defaultHeaders
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly array $defaultHeaders
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $headers = $options['headers'] ?? [];

        // Add default headers unless overridden in request options
        foreach ($this->defaultHeaders as $key => $val) {
            if (!$this->hasHeaderCaseInsensitive($headers, $key)) {
                $headers[$key] = $val;
            }
        }

        $options['headers'] = $headers;

        return $this->client->request($method, $url, $options);
    }

    public function get(string $url, array $options = []): Response
    {
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $options = []): Response
    {
        return $this->request('POST', $url, $options);
    }

    public function put(string $url, array $options = []): Response
    {
        return $this->request('PUT', $url, $options);
    }

    public function patch(string $url, array $options = []): Response
    {
        return $this->request('PATCH', $url, $options);
    }

    public function delete(string $url, array $options = []): Response
    {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * @param array<string, string> $headers
     */
    private function hasHeaderCaseInsensitive(array $headers, string $headerName): bool
    {
        $lowerName = strtolower($headerName);
        foreach (array_keys($headers) as $key) {
            if (strtolower((string) $key) === $lowerName) {
                return true;
            }
        }
        return false;
    }
}
