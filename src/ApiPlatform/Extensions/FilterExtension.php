<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Extensions;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterNotFound;
use Talleu\RedisOm\ApiPlatform\Filters\RedisFilterInterface;

final readonly class FilterExtension implements QueryCollectionExtensionInterface
{
    public function buildParams(array $params, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        foreach ($operation?->getParameters() ?? [] as $parameter) {
            if (null === ($v = $parameter->getValue()) || $v instanceof ParameterNotFound) {
                continue;
            }

            if (null === ($filter = $parameter->getFilter()) || !$filter instanceof RedisFilterInterface) {
                continue;
            }

            $params = $filter->__invoke($params, $parameter, [...$context, ...$parameter->getFilterContext() ?? [], 'operation' => $operation]);
        }

        return $params;
    }
}
