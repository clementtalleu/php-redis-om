<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;

final class RedisProcessor implements ProcessorInterface
{
    public function __construct(private RedisObjectManagerInterface $redisObjectManager)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!\is_object($data)) {
            return $data;
        }

        if ($operation instanceof DeleteOperationInterface) {
            $this->redisObjectManager->remove($data);
            $this->redisObjectManager->flush();

            return null;
        }

        $this->redisObjectManager->persist($data);
        $this->redisObjectManager->flush();

        return $data;
    }
}
