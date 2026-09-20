<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decorates HttpClientInterface to log outgoing HTTP requests, latencies, and responses.
 */
class LoggingHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly string $logLevel = LogLevel::INFO,
        private readonly string $errorLogLevel = LogLevel::ERROR,
    ) {
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $startTime = microtime(true);
        $methodUpper = strtoupper($method);

        try {
            $response = $this->client->request($method, $url, $options);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            $statusCode = $response->getStatusCode();

            $level = $statusCode >= 400 ? $this->errorLogLevel : $this->logLevel;

            $this->logger->log($level, sprintf('HTTP %s %s completed with status %d in %0.2fms', $methodUpper, $url, $statusCode, $durationMs), [
                'method' => $methodUpper,
                'url' => $url,
                'status' => $statusCode,
                'duration_ms' => $durationMs,
            ]);

            return $response;
        } catch (HttpClientException $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->log($this->errorLogLevel, sprintf('HTTP %s %s failed after %0.2fms: %s', $methodUpper, $url, $durationMs, $e->getMessage()), [
                'method' => $methodUpper,
                'url' => $url,
                'duration_ms' => $durationMs,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            throw $e;
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
