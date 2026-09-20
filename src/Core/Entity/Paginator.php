<?php

declare(strict_types=1);

namespace Nqphp\Core\Entity;

use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;

/**
 * Paginator representing a slice of data with total counts and navigation metadata.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 */
class Paginator implements Countable, IteratorAggregate
{
    /**
     * @param array<int, T> $items
     */
    public function __construct(
        private readonly array $items,
        private readonly int $totalItems,
        private readonly int $currentPage,
        private readonly int $perPage,
    ) {
    }

    /**
     * Paginate directly from a QueryBuilder instance.
     *
     * @param QueryBuilder $queryBuilder
     * @param int $page 1-based page number
     * @param int $perPage number of items per page
     * @return self<mixed>
     */
    public static function paginate(QueryBuilder $queryBuilder, int $page = 1, int $perPage = 15): self
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $totalItems = $queryBuilder->count();
        $offset = ($page - 1) * $perPage;

        $items = $queryBuilder
            ->limit($perPage)
            ->offset($offset)
            ->fetch();

        return new self($items, $totalItems, $page, $perPage);
    }

    /**
     * @return array<int, T>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getLastPage(): int
    {
        if ($this->totalItems === 0) {
            return 1;
        }

        return (int) ceil($this->totalItems / $this->perPage);
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getLastPage();
    }

    public function getPreviousPage(): ?int
    {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }

    public function getNextPage(): ?int
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Transform items using a callback and return a new Paginator instance.
     *
     * @template U
     * @param callable(T): U $callback
     * @return self<U>
     */
    public function map(callable $callback): self
    {
        return new self(
            array_map($callback, $this->items),
            $this->totalItems,
            $this->currentPage,
            $this->perPage
        );
    }

    /**
     * Export pagination summary array for JSON / API responses.
     *
     * @return array{
     *     items: array<int, T>,
     *     meta: array{
     *         total: int,
     *         per_page: int,
     *         current_page: int,
     *         last_page: int,
     *         has_next: bool,
     *         has_prev: bool
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'meta' => [
                'total' => $this->totalItems,
                'per_page' => $this->perPage,
                'current_page' => $this->currentPage,
                'last_page' => $this->getLastPage(),
                'has_next' => $this->hasNextPage(),
                'has_prev' => $this->hasPreviousPage(),
            ],
        ];
    }
}
