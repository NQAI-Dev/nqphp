<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that throws an HttpClientException if the HTTP response status code is not within expected ranges.
 */
class StatusCheckingHttpClient implements HttpClientInterface
{
    /**
     * @param HttpClientInterface $client
     * @param bool $throwOnClientError Throw on 4xx statuses (default true)
     * @param bool $throwOnServerError Throw on 5xx statuses (default true)
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly bool $throwOnClientError = true,
        private readonly bool $throwOnServerError = true
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $response = $this->client->request($method, $url, $options);
        $status = $response->getStatusCode();

        if ($this->throwOnClientError && $status >= 400 && $status < 500) {
            throw new HttpClientException(
                "HTTP-запрос {$method} {$url} завершился клиентской ошибкой {$status}."
            );
        }

        if ($this->throwOnServerError && $status >= 500) {
            throw new HttpClientException(
                "HTTP-запрос {$method} {$url} завершился серверной ошибкой {$status}."
            );
        }

        return $response;
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
