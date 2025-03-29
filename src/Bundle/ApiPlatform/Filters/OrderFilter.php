<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\ApiPlatform\Filters;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Operation;

class OrderFilter extends RedisAbstractFilter
{
    public function apply(array $params, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        foreach ($context['filters'] as $property => $value) {
            if ('order' !== $property) {
                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            foreach ($value as $propertyToOrder => $strategy) {
                if (!in_array(strtoupper($strategy), ['ASC', 'DESC'])) {
                    continue;
                }

                if (!property_exists($resourceClass, $propertyToOrder)) {
                    continue;
                }

                if (!$this->isPropertyEnabled($propertyToOrder, $resourceClass, $params)) {
                    continue;
                }
                
                $params['orderBy'][$propertyToOrder] = strtoupper($strategy);
            }
        }

        return $params;
    }


    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}