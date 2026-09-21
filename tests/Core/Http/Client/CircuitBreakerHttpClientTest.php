<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\CircuitBreakerHttpClient;
use Nqphp\Core\Http\Client\CircuitBreakerOpenException;
use Nqphp\Core\Http\Client\CircuitState;
use Nqphp\Core\Http\Client\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CircuitBreakerHttpClientTest extends TestCase
{
    public function testStartsInClosedState(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $cb = new CircuitBreakerHttpClient($mock, failureThreshold: 3, recoveryTimeSeconds: 1.0);

        $this->assertSame(CircuitState::Closed, $cb->getState());
        $this->assertSame(0, $cb->getFailureCount());
    }

    public function testSuccessfulRequestsPassThrough(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.example.com/data', [])
            ->willReturn(new Response('ok', 200));

        $cb = new CircuitBreakerHttpClient($mock, failureThreshold: 2);
        $response = $cb->get('https://api.example.com/data');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
        $this->assertSame(CircuitState::Closed, $cb->getState());
    }

    public function testOpensCircuitWhenFailureThresholdReached(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->method('request')->willThrowException(new RuntimeException('Service unavailable'));

        $cb = new CircuitBreakerHttpClient($mock, failureThreshold: 2, recoveryTimeSeconds: 10.0);

        try {
            $cb->get('https://api.example.com/failing');
        } catch (RuntimeException) {}

        $this->assertSame(CircuitState::Closed, $cb->getState());
        $this->assertSame(1, $cb->getFailureCount());

        try {
            $cb->get('https://api.example.com/failing');
        } catch (RuntimeException) {}

        $this->assertSame(CircuitState::Open, $cb->getState());
        $this->assertSame(2, $cb->getFailureCount());

        $this->expectException(CircuitBreakerOpenException::class);
        $cb->get('https://api.example.com/failing');
    }

    public function testTransitionsToHalfOpenAndRecovers(): void
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->method('request')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new RuntimeException('Error 1')),
                new Response('ok 1', 200),
                new Response('ok 2', 200)
            );

        $cb = new CircuitBreakerHttpClient($mock, failureThreshold: 1, recoveryTimeSeconds: 0.05, halfOpenSuccessThreshold: 2);

        try {
            $cb->get('https://api.example.com/fail');
        } catch (RuntimeException) {}

        $this->assertSame(CircuitState::Open, $cb->getState());

        usleep(60000); // 60ms > 50ms

        $this->assertSame(CircuitState::HalfOpen, $cb->getState());

        $res1 = $cb->get('https://api.example.com/recover');
        $this->assertSame('ok 1', $res1->getContent());

        $res2 = $cb->get('https://api.example.com/recover');
        $this->assertSame('ok 2', $res2->getContent());

        $this->assertSame(CircuitState::Closed, $cb->getState());
        $this->assertSame(0, $cb->getFailureCount());
    }
}
