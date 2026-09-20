<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that injects a default User-Agent header into outgoing HTTP requests if not already set.
 */
final class UserAgentHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $userAgent
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $headers = $options['headers'] ?? [];
        $hasUserAgent = false;

        foreach (array_keys($headers) as $name) {
            if (strcasecmp((string) $name, 'User-Agent') === 0) {
                $hasUserAgent = true;
                break;
            }
        }

        if (!$hasUserAgent) {
            $headers['User-Agent'] = $this->userAgent;
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
