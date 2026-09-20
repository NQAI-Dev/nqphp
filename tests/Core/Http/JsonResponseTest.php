<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http;

use Nqphp\Core\Entity\Paginator;
use Nqphp\Core\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
    public function testSuccessResponseStructure(): void
    {
        $response = JsonResponse::success(['id' => 1, 'name' => 'Alice'], 'User fetched', 200, ['X-Custom' => 'Val']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Val', $response->headers->get('X-Custom'));

        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(['id' => 1, 'name' => 'Alice'], $data['data']);
        $this->assertSame('User fetched', $data['message']);
    }

    public function testPaginatedFromPaginatorInstance(): void
    {
        $items = [['id' => 10], ['id' => 11]];
        $paginator = new Paginator($items, 25, 2, 10);

        $response = JsonResponse::paginated($paginator, 'Page fetched');

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame($items, $data['data']);
        $this->assertSame('Page fetched', $data['message']);
        $this->assertSame([
            'total' => 25,
            'per_page' => 10,
            'current_page' => 2,
            'last_page' => 3,
            'has_next' => true,
            'has_prev' => true,
        ], $data['meta']);
    }

    public function testPaginatedFromArray(): void
    {
        $raw = [
            'items' => ['a', 'b'],
            'meta' => ['total' => 2, 'per_page' => 2],
        ];

        $response = JsonResponse::paginated($raw);
        $data = json_decode((string) $response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame(['a', 'b'], $data['data']);
        $this->assertSame(['total' => 2, 'per_page' => 2], $data['meta']);
        $this->assertArrayNotHasKey('message', $data);
    }

    public function testErrorResponseStructure(): void
    {
        $response = JsonResponse::error('Validation failed', 422, ['email' => ['Invalid format']]);

        $this->assertSame(422, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertSame('Validation failed', $data['error']['message']);
        $this->assertSame(422, $data['error']['code']);
        $this->assertSame(['email' => ['Invalid format']], $data['error']['details']);
    }
}
