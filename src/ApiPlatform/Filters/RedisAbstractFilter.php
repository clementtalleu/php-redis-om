<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\Parameter;

abstract class RedisAbstractFilter implements RedisFilterInterface, JsonSchemaFilterInterface
{
    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $context
     */
    abstract public function __invoke(array $params, ?Parameter $parameter = null, array $context = []): array;

    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'string'];
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
