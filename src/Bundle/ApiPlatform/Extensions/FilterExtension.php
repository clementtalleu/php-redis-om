<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\ApiPlatform\Extensions;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Psr\Container\ContainerInterface;
use Talleu\RedisOm\Bundle\ApiPlatform\Filters\RedisFilterInterface;
use Talleu\RedisOm\Bundle\ApiPlatform\RedisPaginator;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;
use Talleu\RedisOm\Om\Repository\RepositoryInterface;

class FilterExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly ContainerInterface $filterLocator)
    {
    }

    public function buildParams(array $params, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $resourceFilters = $operation?->getFilters();

        if (empty($resourceFilters)) {
            return [];
        }

        $orderFilters = [];
        foreach ($resourceFilters as $filterId) {

            $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;
            if ($filter instanceof RedisFilterInterface) {
                $context['filters'] ??= [];
                $params = $filter->apply($params, $resourceClass, $operation, $context);
            }
        }

        return $params;
    }
}
