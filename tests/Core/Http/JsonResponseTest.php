<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http;

use Nqphp\Core\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
    public function testSuccessEnvelope(): void
    {
        $response = JsonResponse::success(['id' => 1, 'name' => 'Alice'], 'User retrieved');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(['id' => 1, 'name' => 'Alice'], $data['data']);
        $this->assertSame('User retrieved', $data['message']);
    }

    public function testSuccessWithoutMessage(): void
    {
        $response = JsonResponse::success(['ok' => true], null, 201);

        $this->assertSame(201, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(['ok' => true], $data['data']);
        $this->assertArrayNotHasKey('message', $data);
    }

    public function testErrorEnvelope(): void
    {
        $response = JsonResponse::error('Invalid credentials', 401);

        $this->assertSame(401, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('Invalid credentials', $data['error']['message']);
        $this->assertSame(401, $data['error']['code']);
        $this->assertArrayNotHasKey('details', $data['error']);
    }

    public function testErrorWithDetails(): void
    {
        $details = ['email' => ['The email field is required.']];
        $response = JsonResponse::error('Validation failed', 422, $details);

        $this->assertSame(422, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('Validation failed', $data['error']['message']);
        $this->assertSame(422, $data['error']['code']);
        $this->assertSame($details, $data['error']['details']);
    }
}
