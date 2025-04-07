<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Talleu\RedisOm\ApiPlatform\State\CollectionProvider;
use Talleu\RedisOm\ApiPlatform\State\ItemProvider;
use Talleu\RedisOm\ApiPlatform\State\RedisProcessor;
use Talleu\RedisOm\Om\Mapping\Entity;

/**
 * Adds default providers and processors.
 *
 * Adapted from https://github.com/api-platform/core/blob/5e47ca3942dfaa469d8d411d161e49d8d5751bbb/src/Doctrine/Orm/Metadata/Resource/DoctrineOrmResourceCollectionMetadataFactory.php
 *
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
final readonly class ResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $decorated,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        /** @var ApiResource $resourceMetadata */
        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();

            if ($operations) {
                /** @var Operation $operation */
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    $entityClass = $operation->getClass();

                    if (!(new \ReflectionClass($entityClass))->getAttributes(Entity::class)) {
                        continue;
                    }

                    $operations->add($operationName, $this->addDefaults($operation));
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();

            if ($graphQlOperations) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    $entityClass = $graphQlOperation->getClass();

                    if (!(new \ReflectionClass($entityClass))->getAttributes(Entity::class)) {
                        continue;
                    }

                    $graphQlOperations[$operationName] = $this->addDefaults($graphQlOperation);
                }

                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function addDefaults(Operation $operation): Operation
    {
        if (null === $operation->getProvider()) {
            $operation = $operation->withProvider($operation instanceof CollectionOperationInterface ? CollectionProvider::class : ItemProvider::class);
        }

        if (null === $operation->getProcessor()) {
            $operation = $operation->withProcessor(RedisProcessor::class);
        }

        return $operation;
    }
}
