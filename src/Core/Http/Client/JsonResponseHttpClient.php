<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that ensures the request asks for JSON and verifies response content is valid JSON.
 */
class JsonResponseHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly bool $autoAcceptHeader = true
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        if ($this->autoAcceptHeader) {
            $headers = $options['headers'] ?? [];
            if (!isset($headers['Accept'])) {
                $headers['Accept'] = 'application/json';
                $options['headers'] = $headers;
            }
        }

        $response = $this->client->request($method, $url, $options);
        $this->validateJson($response, $url);

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

    private function validateJson(Response $response, string $url): void
    {
        $content = (string) $response->getContent();
        if ($content === '') {
            return;
        }

        json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpClientException(
                "Ответ от {$url} не является валидным JSON: " . json_last_error_msg()
            );
        }
    }
}
