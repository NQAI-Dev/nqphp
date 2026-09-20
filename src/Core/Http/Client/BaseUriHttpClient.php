<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP client decorator that prefixes relative request URLs with a configured base URI.
 */
class BaseUriHttpClient implements HttpClientInterface
{
    private string $baseUri;

    public function __construct(
        private readonly HttpClientInterface $client,
        string $baseUri
    ) {
        $this->baseUri = rtrim($baseUri, '/');
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        return $this->client->request($method, $this->resolveUrl($url), $options);
    }

    public function get(string $url, array $options = []): Response
    {
        return $this->client->get($this->resolveUrl($url), $options);
    }

    public function post(string $url, array $options = []): Response
    {
        return $this->client->post($this->resolveUrl($url), $options);
    }

    public function put(string $url, array $options = []): Response
    {
        return $this->client->put($this->resolveUrl($url), $options);
    }

    public function patch(string $url, array $options = []): Response
    {
        return $this->client->patch($this->resolveUrl($url), $options);
    }

    public function delete(string $url, array $options = []): Response
    {
        return $this->client->delete($this->resolveUrl($url), $options);
    }

    private function resolveUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $trimmed = ltrim($url, '/');
        if ($trimmed === '') {
            return $this->baseUri;
        }

        return $this->baseUri . '/' . $trimmed;
    }
}
