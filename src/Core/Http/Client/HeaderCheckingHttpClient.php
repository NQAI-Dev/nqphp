<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that validates required headers exist in the response or throws an exception.
 */
class HeaderCheckingHttpClient implements HttpClientInterface
{
    /**
     * @param array<int, string> $requiredHeaders
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly array $requiredHeaders
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $response = $this->client->request($method, $url, $options);
        $this->validateHeaders($response, $url);
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

    private function validateHeaders(Response $response, string $url): void
    {
        foreach ($this->requiredHeaders as $header) {
            if (!$response->headers->has($header)) {
                throw new HttpClientException(
                    "Ответ от {$url} не содержит обязательного заголовка: {$header}"
                );
            }
        }
    }
}
