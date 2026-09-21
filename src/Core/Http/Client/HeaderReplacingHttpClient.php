<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that overrides or replaces specified request headers, ensuring specific values are always sent.
 */
class HeaderReplacingHttpClient implements HttpClientInterface
{
    /**
     * @param array<string, string> $headersToReplace Map of header names to values
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly array $headersToReplace
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $headers = $options['headers'] ?? [];
        if (!is_array($headers)) {
            $headers = [];
        }

        foreach ($this->headersToReplace as $headerName => $headerValue) {
            $normalizedName = strtolower($headerName);
            foreach ($headers as $existingName => $val) {
                if (strtolower((string) $existingName) === $normalizedName) {
                    unset($headers[$existingName]);
                }
            }
            $headers[$headerName] = (string) $headerValue;
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
}
