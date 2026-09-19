<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Entity;

use Nqphp\Core\Entity\Driver\DriverInterface;
use Nqphp\Core\Entity\Paginator;
use Nqphp\Core\Entity\QueryBuilder;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    private DriverInterface $driver;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);
    }

    public function testPaginateCalculatesPagesCorrectly(): void
    {
        $this->driver->expects($this->once())
            ->method('count')
            ->with('posts', ['status' => 'published'])
            ->willReturn(55);

        $fakeItems = [
            ['id' => 11, 'title' => 'Post 11'],
            ['id' => 12, 'title' => 'Post 12'],
        ];

        $this->driver->expects($this->once())
            ->method('findBy')
            ->with('posts', ['status' => 'published'], ['id' => 'desc'], 10, 10)
            ->willReturn($fakeItems);

        $qb = QueryBuilder::for('posts', $this->driver)
            ->where('status', 'published')
            ->orderBy('id', 'DESC');

        $paginator = $qb->toPaginator(page: 2, perPage: 10);

        $this->assertSame(55, $paginator->getTotalItems());
        $this->assertSame(2, $paginator->getCurrentPage());
        $this->assertSame(10, $paginator->getPerPage());
        $this->assertSame(6, $paginator->getLastPage());
        $this->assertTrue($paginator->hasPreviousPage());
        $this->assertTrue($paginator->hasNextPage());
        $this->assertSame(1, $paginator->getPreviousPage());
        $this->assertSame(3, $paginator->getNextPage());
        $this->assertCount(2, $paginator);
        $this->assertSame($fakeItems, $paginator->getItems());
        $this->assertSame($fakeItems, iterator_to_array($paginator));

        $array = $paginator->toArray();
        $this->assertSame($fakeItems, $array['items']);
        $this->assertSame([
            'total' => 55,
            'per_page' => 10,
            'current_page' => 2,
            'last_page' => 6,
            'has_next' => true,
            'has_prev' => true,
        ], $array['meta']);
    }

    public function testPaginateFirstAndLastPageBoundaries(): void
    {
        $this->driver->method('count')->willReturn(20);
        $this->driver->method('findBy')->willReturn([]);

        $qb = QueryBuilder::for('items', $this->driver);

        $firstPage = $qb->toPaginator(page: 1, perPage: 10);
        $this->assertFalse($firstPage->hasPreviousPage());
        $this->assertNull($firstPage->getPreviousPage());
        $this->assertTrue($firstPage->hasNextPage());
        $this->assertSame(2, $firstPage->getNextPage());

        $lastPage = $qb->toPaginator(page: 2, perPage: 10);
        $this->assertTrue($lastPage->hasPreviousPage());
        $this->assertSame(1, $lastPage->getPreviousPage());
        $this->assertFalse($lastPage->hasNextPage());
        $this->assertNull($lastPage->getNextPage());
    }

    public function testPaginateEmptySet(): void
    {
        $this->driver->method('count')->willReturn(0);
        $this->driver->method('findBy')->willReturn([]);

        $qb = QueryBuilder::for('empty', $this->driver);
        $paginator = $qb->toPaginator(page: 1, perPage: 15);

        $this->assertSame(0, $paginator->getTotalItems());
        $this->assertSame(1, $paginator->getLastPage());
        $this->assertFalse($paginator->hasPreviousPage());
        $this->assertFalse($paginator->hasNextPage());
        $this->assertSame(0, $paginator->count());
        $this->assertSame([], $paginator->getItems());
    }
}
