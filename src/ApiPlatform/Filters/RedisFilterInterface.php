<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Parameter;

interface RedisFilterInterface extends FilterInterface
{
    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function __invoke(array $params, Parameter $parameter = null, array $context = []): array;
}
