<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\ApiPlatform\State;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use Psr\Container\ContainerInterface;
use Talleu\RedisOm\Bundle\ApiPlatform\Extensions\QueryCollectionExtensionInterface;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;
use Talleu\RedisOm\Om\Repository\RepositoryInterface;

class CollectionProvider implements ProviderInterface
{
    /**
     * @param QueryCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(private RedisObjectManagerInterface $redisObjectManager, private readonly iterable $collectionExtensions = [])
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $entityClass = $operation->getClass();

        $params = [
            'criteria' => []
        ];

        foreach ($this->collectionExtensions as $extension) {
            $params = $extension->buildParams($params, $entityClass, $operation, $context);
        }

        $method = 'findBy';
        if (array_key_exists('search_strategy', $params)) {
            $method = 'findByLike';
            unset($params['search_strategy']);
        }

        return $this->redisObjectManager->getRepository($entityClass)->$method(...$params);
    }
}
