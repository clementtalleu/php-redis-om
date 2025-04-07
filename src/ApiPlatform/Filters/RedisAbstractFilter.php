<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;

abstract class RedisAbstractFilter implements RedisFilterInterface
{
    public function __construct(protected ?array $properties = null)
    {
    }

    abstract public function apply(array $params, string $resourceClass, ?Operation $operation = null, array $context = []): array;

    public function isPropertyEnabled(string $property, string $resourceClass): bool
    {
        $reflectionClass = new \ReflectionClass($resourceClass);
        $reflectionApiFilters = $reflectionClass->getAttributes(ApiFilter::class);
        if ([] === $reflectionApiFilters) {
            return false;
        }

        foreach ($reflectionApiFilters as $reflectionApiFilter) {
            /** @var ApiFilter $filter */
            $filter = $reflectionApiFilter->newInstance();

            if ($filter->filterClass !== static::class) {
                continue;
            }

            $availablePropertiesFilters = $filter->properties;

            if (in_array(static::class, [OrderFilter::class, NumericFilter::class]) && in_array($property, $availablePropertiesFilters)) {
                return true;
            }

            if (SearchFilter::class === static::class && array_key_exists($property, $availablePropertiesFilters)) {
                $this->properties = $availablePropertiesFilters;
                return true;
            }
        }

        return false;
    }
}
