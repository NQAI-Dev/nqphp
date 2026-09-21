<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that injects a static set of default HTTP headers into every outgoing request if not already present.
 */
class HeaderClient implements HttpClientInterface
{
    /**
     * @param HttpClientInterface $client
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
        if (!is_array($headers)) {
            $headers = [];
        }

        foreach ($this->defaultHeaders as $name => $value) {
            $exists = false;
            foreach (array_keys($headers) as $key) {
                if (strcasecmp((string) $key, (string) $name) === 0) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $headers[$name] = $value;
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
}
