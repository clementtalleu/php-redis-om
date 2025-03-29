<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\ApiPlatform;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\State\Pagination\HasNextPagePaginatorInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Talleu\RedisOm\Om\Repository\RepositoryInterface;

final class RedisPaginator implements PaginatorInterface, HasNextPagePaginatorInterface, \IteratorAggregate
{
    protected RepositoryInterface $repository;
    protected array|\Traversable $iterator;
    protected ?int $firstResult;
    protected ?int $maxResults;
    protected array $params;
    private ?int $totalItems = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(RepositoryInterface $repository, array $params)
    {
        $this->repository = $repository;
        $this->params = $params;
        $this->firstResult = $params['offset'];
        $this->maxResults = $params['limit'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return floor($this->firstResult / $this->maxResults) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return (float)$this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        $result = $this->repository->findBy(...$this->params);

        return new \ArrayIterator($result);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return ceil($this->getTotalItems() / $this->maxResults) ?: 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        return (float)($this->totalItems ?? $this->totalItems = $this->repository->count($this->params['criteria'] ?? []));
    }

    /**
     * {@inheritdoc}
     */
    public function hasNextPage(): bool
    {
        if (isset($this->totalItems)) {
            return $this->totalItems > ($this->firstResult + $this->maxResults);
        }

        return true;
    }
}
