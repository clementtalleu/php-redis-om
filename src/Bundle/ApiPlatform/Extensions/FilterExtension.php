<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\ApiPlatform\Extensions;

use ApiPlatform\Metadata\Operation;
use Psr\Container\ContainerInterface;
use Talleu\RedisOm\Bundle\ApiPlatform\Filters\RedisFilterInterface;

final readonly class FilterExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private ContainerInterface $filterLocator)
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
