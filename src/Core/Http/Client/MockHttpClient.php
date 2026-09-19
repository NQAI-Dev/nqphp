<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

final class MockHttpClient implements HttpClientInterface
{
    /** @var array<int, array{method: string, url: string, options: array<string, mixed>}> */
    private array $requests = [];

    /** @var array<int, Response|callable> */
    private array $queue = [];

    /**
     * @param array<int, Response|callable> $queue
     */
    public function __construct(array $queue = [])
    {
        $this->queue = array_values($queue);
    }

    public function queueResponse(Response|callable $response): self
    {
        $this->queue[] = $response;
        return $this;
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $this->requests[] = [
            'method' => strtoupper($method),
            'url' => $url,
            'options' => $options,
        ];

        if (empty($this->queue)) {
            return new Response('', 200);
        }

        $next = array_shift($this->queue);
        if (is_callable($next)) {
            $resp = $next($method, $url, $options);
            return $resp instanceof Response ? $resp : new Response((string) $resp, 200);
        }

        return $next;
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

    /**
     * @return array<int, array{method: string, url: string, options: array<string, mixed>}>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * @return array{method: string, url: string, options: array<string, mixed>}|null
     */
    public function getLastRequest(): ?array
    {
        if (empty($this->requests)) {
            return null;
        }
        return $this->requests[count($this->requests) - 1];
    }

    public function count(): int
    {
        return count($this->requests);
    }

    public function reset(): void
    {
        $this->requests = [];
        $this->queue = [];
    }
}
