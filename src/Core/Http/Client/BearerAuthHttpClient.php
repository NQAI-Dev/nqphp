<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator automatically injecting Authorization: Bearer <token> into requests.
 */
class BearerAuthHttpClient implements HttpClientInterface
{
    /**
     * @param HttpClientInterface $client
     * @param string|callable(): string $token Token string or factory resolving token dynamically
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly mixed $token
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $token = is_callable($this->token) ? ($this->token)() : $this->token;

        $headers = $options['headers'] ?? [];
        if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
            $headers['Authorization'] = 'Bearer ' . $token;
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
