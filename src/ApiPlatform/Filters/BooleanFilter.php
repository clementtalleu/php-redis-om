<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\Parameter;

class BooleanFilter extends RedisAbstractFilter
{
    public function __invoke(array $params, ?Parameter $parameter = null, array $context = []): array
    {
        $raw = $parameter->getValue();
        $normalized = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($normalized === null) {
            return $params;
        }

        $params['criteria'][$parameter->getProperty() ?? $parameter->getKey()] = $normalized ? 'true' : 'false';

        return $params;
    }
}
