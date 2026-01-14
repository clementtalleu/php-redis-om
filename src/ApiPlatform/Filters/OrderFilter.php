<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\Parameter;

class OrderFilter extends RedisAbstractFilter
{
    public function __construct(private readonly array $properties = [])
    {
    }

    public function __invoke(array $params, ?Parameter $parameter = null, array $context = []): array
    {
        // todo: use constraint validations instead
        if (is_null($parameter)) {
            return $params;
        }

        $property = $parameter->getProperty();
        $value = $parameter->getValue();

        // API Platform 4.1 : getValue() return an array ["age" => "ASC"]
        if (is_array($value)) {
            foreach ($value as $prop => $direction) {
                if (!in_array($prop, $this->properties, true)) {
                    continue;
                }

                if (!in_array(strtoupper($direction), ['ASC', 'DESC'], true)) {
                    continue;
                }

                $params['orderBy'][$prop] = strtoupper($direction);
            }

            return $params;
        }


        // API Platform 4.2+ : getValue() return a string "ASC" et getProperty() return "age"
        if (!in_array($property, $this->properties, true)) {
            return $params;
        }

        if (!is_string($value) || !in_array(strtoupper($value), ['ASC', 'DESC'], true)) {
            return $params;
        }

        $params['orderBy'][$property] = strtoupper($value);

        return $params;
    }
}
