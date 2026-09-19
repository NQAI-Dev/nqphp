<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class MockHttpClientTest extends TestCase
{
    public function testQueueStaticResponses(): void
    {
        $mock = new MockHttpClient([
            new Response('First response', 200, ['content-type' => 'text/plain']),
            new Response(json_encode(['status' => 'ok']), 201, ['content-type' => 'application/json']),
        ]);

        $res1 = $mock->get('https://api.example.com/one', ['query' => ['foo' => 'bar']]);
        self::assertSame(200, $res1->getStatusCode());
        self::assertSame('First response', $res1->getContent());

        $res2 = $mock->post('https://api.example.com/two', ['json' => ['name' => 'test']]);
        self::assertSame(201, $res2->getStatusCode());
        self::assertSame('{"status":"ok"}', $res2->getContent());

        // Exhausted queue returns default 200 empty response
        $res3 = $mock->delete('https://api.example.com/three');
        self::assertSame(200, $res3->getStatusCode());
        self::assertSame('', $res3->getContent());

        self::assertSame(3, $mock->count());
        $last = $mock->getLastRequest();
        self::assertNotNull($last);
        self::assertSame('DELETE', $last['method']);
        self::assertSame('https://api.example.com/three', $last['url']);
    }

    public function testQueueDynamicCallableResponses(): void
    {
        $mock = new MockHttpClient();
        $mock->queueResponse(function (string $method, string $url, array $options): Response {
            return new Response("Received {$method} to {$url}", 202);
        });

        $resp = $mock->put('https://api.example.com/update', ['body' => 'data']);
        self::assertSame(202, $resp->getStatusCode());
        self::assertSame('Received PUT to https://api.example.com/update', $resp->getContent());

        $reqs = $mock->getRequests();
        self::assertCount(1, $reqs);
        self::assertSame('PUT', $reqs[0]['method']);
        self::assertSame(['body' => 'data'], $reqs[0]['options']);
    }

    public function testReset(): void
    {
        $mock = new MockHttpClient([new Response('A')]);
        $mock->get('https://example.com');
        self::assertSame(1, $mock->count());

        $mock->reset();
        self::assertSame(0, $mock->count());
        self::assertNull($mock->getLastRequest());

        // Queue was cleared by reset
        $resp = $mock->get('https://example.com');
        self::assertSame('', $resp->getContent());
        self::assertSame(1, $mock->count());
    }
}
