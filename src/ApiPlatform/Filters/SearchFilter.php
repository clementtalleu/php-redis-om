<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\Parameter;

class SearchFilter extends RedisAbstractFilter
{
    public function __construct(private readonly SearchStrategy $strategy = SearchStrategy::Exact)
    {
    }

    public function __invoke(array $params, ?Parameter $parameter = null, ?array $context = []): array
    {
        $params['search_strategy'] = $this->strategy;
        $params['criteria'][$parameter->getProperty() ?? $parameter->getKey()] = $parameter->getValue();

        return $params;
    }
}
