<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator enforcing default or maximum request timeouts for HttpClientInterface.
 */
class TimeoutHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly float $defaultTimeout = 10.0,
        private readonly ?float $maxTimeout = null
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $timeout = $options['timeout'] ?? $this->defaultTimeout;

        if ($this->maxTimeout !== null && $timeout > $this->maxTimeout) {
            $timeout = $this->maxTimeout;
        }

        $options['timeout'] = (float) $timeout;

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
