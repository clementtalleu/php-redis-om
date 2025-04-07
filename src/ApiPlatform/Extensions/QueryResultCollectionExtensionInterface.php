<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Extensions;

use ApiPlatform\Metadata\Operation;

interface QueryResultCollectionExtensionInterface extends QueryCollectionExtensionInterface
{
    public function buildParams(array $params, string $resourceClass, ?Operation $operation = null, array $context = []): array;

    public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool;

    public function getResult(array $params, ?string $resourceClass = null): iterable;
}
