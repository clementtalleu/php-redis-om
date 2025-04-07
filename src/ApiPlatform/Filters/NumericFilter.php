<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Operation;

class NumericFilter extends RedisAbstractFilter
{
    public function apply(array $params, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        foreach ($context['filters'] as $property => $value) {
            if (!property_exists($resourceClass, $property)) {
                continue;
            }

            if (!$this->isPropertyEnabled($property, $resourceClass)) {
                continue;
            }

            $params['criteria'][$property] = $value;
        }

        return $params;
    }


    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
