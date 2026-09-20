<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that automatically appends default query parameters to outgoing requests.
 */
class QueryParamHttpClient implements HttpClientInterface
{
    /**
     * @param array<string, scalar|null> $defaultParams
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly array $defaultParams
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        return $this->client->request($method, $this->buildUrl($url), $options);
    }

    public function get(string $url, array $options = []): Response
    {
        return $this->client->get($this->buildUrl($url), $options);
    }

    public function post(string $url, array $options = []): Response
    {
        return $this->client->post($this->buildUrl($url), $options);
    }

    public function put(string $url, array $options = []): Response
    {
        return $this->client->put($this->buildUrl($url), $options);
    }

    public function patch(string $url, array $options = []): Response
    {
        return $this->client->patch($this->buildUrl($url), $options);
    }

    public function delete(string $url, array $options = []): Response
    {
        return $this->client->delete($this->buildUrl($url), $options);
    }

    private function buildUrl(string $url): string
    {
        if (empty($this->defaultParams)) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $existingQuery = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $existingQuery);
        }

        // Default params are merged: existing query params take precedence
        $mergedQuery = array_merge($this->defaultParams, $existingQuery);

        $queryString = http_build_query($mergedQuery);

        $newUrl = '';
        if (isset($parts['scheme'])) {
            $newUrl .= $parts['scheme'] . '://';
        }
        if (isset($parts['user'])) {
            $newUrl .= $parts['user'];
            if (isset($parts['pass'])) {
                $newUrl .= ':' . $parts['pass'];
            }
            $newUrl .= '@';
        }
        if (isset($parts['host'])) {
            $newUrl .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $newUrl .= ':' . $parts['port'];
        }
        if (isset($parts['path'])) {
            $newUrl .= $parts['path'];
        }
        if ($queryString !== '') {
            $newUrl .= '?' . $queryString;
        }
        if (isset($parts['fragment'])) {
            $newUrl .= '#' . $parts['fragment'];
        }

        return $newUrl;
    }
}
