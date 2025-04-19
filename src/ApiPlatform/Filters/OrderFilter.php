<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\Parameter;

class OrderFilter extends RedisAbstractFilter
{
    public function __construct(private readonly array $properties = [])
    {
    }

    public function __invoke(array $params, Parameter $parameter = null, array $context = []): array
    {
        // todo: use constraint validations instead
        if (!is_array($parameter->getValue())) {
            return $params;
        }

        foreach ($parameter->getValue([]) as $prop => $value) {
            if (!in_array($prop, $this->properties, true)) {
                continue;
            }

            // could also use validation constraints
            if (!in_array(strtoupper($value), ['ASC', 'DESC'])) {
                continue;
            }


            $params['orderBy'][$prop] = strtoupper($value);
        }

        return $params;
    }
}
