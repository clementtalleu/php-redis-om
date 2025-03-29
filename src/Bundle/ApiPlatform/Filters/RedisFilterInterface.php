<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\ApiPlatform\Filters;

use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Operation;

interface RedisFilterInterface extends FilterInterface
{
    public function apply(array $params, string $resourceClass, ?Operation $operation = null, array $context = []): array;
}
