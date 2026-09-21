<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorator that sets a default 'Content-Type' header for outgoing requests with a body if not explicitly specified.
 */
class ContentTypeHeaderHttpClient implements HttpClientInterface
{
    /**
     * @param HttpClientInterface $client The underlying HTTP client
     * @param string $contentType Default Content-Type header value (e.g., 'application/json')
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $contentType = 'application/json'
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $headers = $options['headers'] ?? [];
        if (!is_array($headers)) {
            $headers = [];
        }

        $hasBody = isset($options['body']) || isset($options['json']);

        if ($hasBody) {
            $hasContentType = false;
            foreach (array_keys($headers) as $key) {
                if (strcasecmp((string) $key, 'Content-Type') === 0) {
                    $hasContentType = true;
                    break;
                }
            }

            if (!$hasContentType) {
                $headers['Content-Type'] = $this->contentType;
                $options['headers'] = $headers;
            }
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
