<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decorates HttpClientInterface to provide automatic retries for transient failures.
 */
class RetryHttpClient implements HttpClientInterface
{
    /**
     * @param HttpClientInterface $client Inner client
     * @param int $maxRetries Maximum retry attempts (default 3)
     * @param int $baseDelayMs Initial backoff delay in milliseconds (default 100)
     * @param list<int> $retryStatuses HTTP status codes that trigger a retry
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly int $maxRetries = 3,
        private readonly int $baseDelayMs = 100,
        private readonly array $retryStatuses = [429, 500, 502, 503, 504],
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $attempts = 0;

        while (true) {
            $attempts++;
            try {
                $response = $this->client->request($method, $url, $options);

                if (!in_array($response->getStatusCode(), $this->retryStatuses, true) || $attempts > $this->maxRetries) {
                    return $response;
                }
            } catch (HttpClientException $e) {
                if ($attempts > $this->maxRetries) {
                    throw $e;
                }
            }

            $delayMs = $this->baseDelayMs * (2 ** ($attempts - 1));
            usleep($delayMs * 1000);
        }
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
