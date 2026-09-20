<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator automatically injecting Authorization: Basic <base64> into requests.
 */
class BasicAuthHttpClient implements HttpClientInterface
{
    private string $authHeader;

    public function __construct(
        private readonly HttpClientInterface $client,
        string $username,
        string $password
    ) {
        $this->authHeader = 'Basic ' . base64_encode($username . ':' . $password);
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $headers = $options['headers'] ?? [];
        if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
            $headers['Authorization'] = $this->authHeader;
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
