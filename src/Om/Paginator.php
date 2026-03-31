<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om;

/**
 * @template T of object
 * @implements \IteratorAggregate<int, T>
 */
class Paginator implements \IteratorAggregate, \Countable
{
    /**
     * @param T[] $items
     */
    public function __construct(
        private readonly array $items,
        private readonly int $totalItems,
        private readonly int $currentPage,
        private readonly int $itemsPerPage,
    ) {
    }

    /**
     * @return T[]
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

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getTotalPages(): int
    {
        if ($this->itemsPerPage <= 0) {
            return 0;
        }

        return (int) ceil($this->totalItems / $this->itemsPerPage);
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
