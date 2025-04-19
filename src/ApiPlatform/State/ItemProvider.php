<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;

class ItemProvider implements ProviderInterface
{
    public function __construct(private RedisObjectManagerInterface $redisObjectManager)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        return $this->redisObjectManager->getRepository($operation->getClass())->find($uriVariables['id']) ?? null;
    }
}
