<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that strips specified request headers before dispatching downstream.
 */
class HeaderRemovingHttpClient implements HttpClientInterface
{
    /**
     * @param array<int, string> $headersToRemove
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly array $headersToRemove
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        if (isset($options['headers']) && is_array($options['headers'])) {
            $headers = $options['headers'];
            $normalizedToRemove = array_map('strtolower', $this->headersToRemove);

            foreach ($headers as $key => $value) {
                if (in_array(strtolower((string) $key), $normalizedToRemove, true)) {
                    unset($headers[$key]);
                }
            }

            $options['headers'] = $headers;
        }

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
