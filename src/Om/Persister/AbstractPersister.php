<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister;

use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Key\KeyGenerator;
use Talleu\RedisOm\Om\Mapping\Entity;

abstract class AbstractPersister implements PersisterInterface
{
    public function __construct(
        private ?KeyGenerator $keyGenerator = null,
        protected ?RedisClientInterface $redis = null
    ) {
        $this->redis = $redis ?? (new RedisClient());

        $this->keyGenerator = $keyGenerator ?? new KeyGenerator();
    }

    public function persist(Entity $objectMapper, $object): ObjectToPersist
    {
        $key = $this->keyGenerator->generateKey($objectMapper, $object);

        return new ObjectToPersist(
            persisterClass: get_class($this),
            operation: PersisterOperations::OPERATION_PERSIST->value,
            redisKey: $key,
            converter: $objectMapper->converter,
            value: $object,
        );
    }

    public function delete(Entity $objectMapper, $object): ObjectToPersist
    {
        $identifier = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $key = sprintf('%s:%s', $objectMapper->prefix ?: get_class($object), $object->{$identifier->getName()});
        return new ObjectToPersist(
            persisterClass: get_class($this),
            operation: PersisterOperations::OPERATION_DELETE->value,
            redisKey: $key,
        );
    }

    /**
     * @inheritdoc
     */
    abstract public function doPersist(array $objectsToPersist): void;

    /**
     * @inheritdoc
     */
    abstract public function doDelete(array $objectsToRemove): void;
}
