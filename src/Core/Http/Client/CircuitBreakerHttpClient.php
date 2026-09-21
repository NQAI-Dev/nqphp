<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

enum CircuitState: string
{
    case Closed = 'closed';
    case Open = 'open';
    case HalfOpen = 'half_open';
}

class CircuitBreakerOpenException extends HttpClientException
{
}

class CircuitBreakerHttpClient implements HttpClientInterface
{
    private CircuitState $state = CircuitState::Closed;
    private int $failureCount = 0;
    private int $successCount = 0;
    private ?float $lastFailureTime = null;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly int $failureThreshold = 5,
        private readonly float $recoveryTimeSeconds = 30.0,
        private readonly int $halfOpenSuccessThreshold = 2,
    ) {}

    public function getState(): CircuitState
    {
        $this->checkRecovery();
        return $this->state;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    public function reset(): void
    {
        $this->state = CircuitState::Closed;
        $this->failureCount = 0;
        $this->successCount = 0;
        $this->lastFailureTime = null;
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $this->checkRecovery();

        if ($this->state === CircuitState::Open) {
            throw new CircuitBreakerOpenException(sprintf(
                'Circuit breaker is OPEN. Fast failing request to "%s %s".',
                $method,
                $url
            ));
        }

        try {
            $response = $this->client->request($method, $url, $options);
            $this->onSuccess();
            return $response;
        } catch (Throwable $e) {
            $this->onFailure();
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

    public function delete(string $url, array $options = []): Response
    {
        return $this->request('DELETE', $url, $options);
    }

    public function patch(string $url, array $options = []): Response
    {
        return $this->request('PATCH', $url, $options);
    }

    private function checkRecovery(): void
    {
        if ($this->state === CircuitState::Open && $this->lastFailureTime !== null) {
            if ((microtime(true) - $this->lastFailureTime) >= $this->recoveryTimeSeconds) {
                $this->state = CircuitState::HalfOpen;
                $this->successCount = 0;
            }
        }
    }

    private function onSuccess(): void
    {
        if ($this->state === CircuitState::HalfOpen) {
            $this->successCount++;
            if ($this->successCount >= $this->halfOpenSuccessThreshold) {
                $this->reset();
            }
        } elseif ($this->state === CircuitState::Closed) {
            $this->failureCount = 0;
        }
    }

    private function onFailure(): void
    {
        $this->failureCount++;
        $this->lastFailureTime = microtime(true);

        if ($this->state === CircuitState::HalfOpen) {
            $this->state = CircuitState::Open;
        } elseif ($this->failureCount >= $this->failureThreshold) {
            $this->state = CircuitState::Open;
        }
    }
}
