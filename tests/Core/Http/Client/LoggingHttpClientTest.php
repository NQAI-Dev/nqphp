<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Client;

use Nqphp\Core\Http\Client\HttpClientException;
use Nqphp\Core\Http\Client\LoggingHttpClient;
use Nqphp\Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;

class LoggingHttpClientTest extends TestCase
{
    public function testLogsSuccessfulRequest(): void
    {
        $mock = new MockHttpClient([
            new Response('OK', 200),
        ]);

        $logger = new class () extends AbstractLogger {
            /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
            public array $logs = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->logs[] = [
                    'level' => (string) $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };

        $client = new LoggingHttpClient($mock, $logger);
        $response = $client->get('https://api.example.com/data', ['query' => ['q' => 'test']]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $logger->logs);
        $this->assertSame(LogLevel::INFO, $logger->logs[0]['level']);
        $this->assertStringContainsString('HTTP GET https://api.example.com/data completed with status 200', $logger->logs[0]['message']);
        $this->assertSame('GET', $logger->logs[0]['context']['method']);
        $this->assertSame(200, $logger->logs[0]['context']['status']);
    }

    public function testLogs4xxStatusAsErrorLevel(): void
    {
        $mock = new MockHttpClient([
            new Response('Not Found', 404),
        ]);

        $logger = new class () extends AbstractLogger {
            /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
            public array $logs = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->logs[] = [
                    'level' => (string) $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };

        $client = new LoggingHttpClient($mock, $logger);
        $response = $client->post('https://api.example.com/items');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertCount(1, $logger->logs);
        $this->assertSame(LogLevel::ERROR, $logger->logs[0]['level']);
        $this->assertSame(404, $logger->logs[0]['context']['status']);
    }

    public function testLogsExceptionAndRethrows(): void
    {
        $mock = new MockHttpClient([
            function () {
                throw new HttpClientException('Connection timed out after 5.0s');
            },
        ]);

        $logger = new class () extends AbstractLogger {
            /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
            public array $logs = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->logs[] = [
                    'level' => (string) $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };

        $client = new LoggingHttpClient($mock, $logger);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Connection timed out');

        try {
            $client->delete('https://api.example.com/resource/123');
        } finally {
            $this->assertCount(1, $logger->logs);
            $this->assertSame(LogLevel::ERROR, $logger->logs[0]['level']);
            $this->assertStringContainsString('HTTP DELETE https://api.example.com/resource/123 failed', $logger->logs[0]['message']);
            $this->assertSame('DELETE', $logger->logs[0]['context']['method']);
            $this->assertArrayHasKey('duration_ms', $logger->logs[0]['context']);
        }
    }
}
