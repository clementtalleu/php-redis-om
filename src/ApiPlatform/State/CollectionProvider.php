<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Talleu\RedisOm\ApiPlatform\Extensions\QueryCollectionExtensionInterface;
use Talleu\RedisOm\ApiPlatform\Extensions\QueryResultCollectionExtensionInterface;
use Talleu\RedisOm\ApiPlatform\Filters\SearchStrategy;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;

class CollectionProvider implements ProviderInterface
{
    /**
     * @param QueryCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(private RedisObjectManagerInterface $redisObjectManager, private readonly iterable $collectionExtensions = [])
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $entityClass = $operation->getClass();

        $params = [
            'criteria' => []
        ];

        foreach ($this->collectionExtensions as $extension) {
            $params = $extension->buildParams($params, $entityClass, $operation, $context);

            if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($entityClass, $operation, $context)) {

                return $extension->getResult($params, $entityClass);
            }
        }

        $method = 'findBy';
        if (array_key_exists('search_strategy', $params) && $params['search_strategy'] !== SearchStrategy::Exact) {
            $method = match($params['search_strategy']) {
                SearchStrategy::Partial => 'findByLike',
                SearchStrategy::Start => 'findByStartWith',
                SearchStrategy::End => 'findByEndWith',
                default => $method,
            };
            unset($params['search_strategy']);
        }

        return $this->redisObjectManager->getRepository($entityClass)->$method(...$params);
    }
}
