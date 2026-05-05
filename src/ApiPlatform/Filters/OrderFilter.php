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
        $property = $parameter->getProperty();
        $value = $parameter->getValue();

        // API Platform 4.1: getValue() returns an array ["age" => "ASC"]
        if (is_array($value)) {
            foreach ($value as $prop => $direction) {
                if (!in_array($prop, $this->properties, true)) {
                    continue;
                }

                $params['orderBy'][$prop] = strtoupper((string) $direction);
            }

            return $params;
        }

        // API Platform 4.2+: getValue() returns "ASC", getProperty() returns "age"
        if (!in_array($property, $this->properties, true)) {
            return $params;
        }

        $params['orderBy'][$property] = strtoupper((string) $value);

        return $params;
    }

    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'string', 'enum' => ['ASC', 'DESC', 'asc', 'desc']];
    }
}
