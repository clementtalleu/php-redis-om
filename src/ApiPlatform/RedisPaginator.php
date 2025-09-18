<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\State\Pagination\HasNextPagePaginatorInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Talleu\RedisOm\ApiPlatform\Filters\SearchStrategy;
use Talleu\RedisOm\Om\Repository\RepositoryInterface;

final class RedisPaginator implements PaginatorInterface, HasNextPagePaginatorInterface, \IteratorAggregate
{
    protected RepositoryInterface $repository;
    protected array|\Traversable $iterator;
    protected ?int $firstResult;
    protected ?int $maxResults;
    protected array $params;
    private ?int $totalItems = null;
    private SearchStrategy $strategy = SearchStrategy::Exact;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(RepositoryInterface $repository, array $params)
    {
        $this->repository = $repository;
        $this->params = $params;
        $this->firstResult = $params['offset'];
        $this->maxResults = $params['limit'];

        if (array_key_exists('search_strategy', $params)) {
            $this->strategy = $params['search_strategy'];
            unset($this->params['search_strategy']);
        }
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
        return (float) $this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        $method = match($this->strategy) {
            SearchStrategy::Exact => 'findBy',
            SearchStrategy::Partial => 'findByLike',
            SearchStrategy::Start => 'findByStartWith',
            SearchStrategy::End => 'findByEndWith',
        };

        return new \ArrayIterator($this->repository->$method(...$this->params));
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
        $criteria = $this->params['criteria'] ?? [];
        $method = $this->strategy === SearchStrategy::Exact ? 'count' : 'countByLike';

        return (float)($this->totalItems ?? $this->totalItems = $this->repository->$method($criteria));
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
