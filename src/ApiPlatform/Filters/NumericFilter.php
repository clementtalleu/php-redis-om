<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\Parameter;

class NumericFilter extends RedisAbstractFilter
{
    public function __invoke(array $params, ?Parameter $parameter = null, array $context = []): array
    {
        $params['criteria'][$parameter->getProperty() ?? $parameter->getKey()] = $parameter->getValue();
        return $params;
    }
}
