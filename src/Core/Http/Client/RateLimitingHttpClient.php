<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Nqphp\Core\Http\RateLimit\RateLimiterInterface;
use Symfony\Component\HttpFoundation\Response;

class RateLimitExceededException extends HttpClientException
{
    public function __construct(
        string $message,
        public readonly int $retryAfter,
    ) {
        parent::__construct($message);
    }
}

class RateLimitingHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly RateLimiterInterface $limiter,
        private readonly int $maxAttempts = 60,
        private readonly int $decaySeconds = 60,
        private readonly ?string $key = null,
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $key = $this->key ?? (string) (parse_url($url, PHP_URL_HOST) ?: 'default');

        $result = $this->limiter->hit($key, $this->maxAttempts, $this->decaySeconds);

        if (!$result['allowed']) {
            throw new RateLimitExceededException(sprintf(
                'Rate limit exceeded for "%s". Retry after %d seconds.',
                $key,
                $result['retry_after']
            ), $result['retry_after']);
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

    public function delete(string $url, array $options = []): Response
    {
        return $this->request('DELETE', $url, $options);
    }

    public function patch(string $url, array $options = []): Response
    {
        return $this->request('PATCH', $url, $options);
    }
}
