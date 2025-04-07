<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Extensions;

use ApiPlatform\Metadata\Operation;

interface QueryCollectionExtensionInterface
{
    public function buildParams(array $params, string $resourceClass, ?Operation $operation = null, array $context = []): array;
}
