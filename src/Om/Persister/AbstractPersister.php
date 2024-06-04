<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister;

use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Key\KeyGenerator;
use Talleu\RedisOm\Om\Mapping\Entity;

abstract class AbstractPersister implements PersisterInterface
{
    protected RedisClientInterface $redis;

    public function __construct(private ?KeyGenerator $keyGenerator = null)
    {
        $this->redis = (new RedisClient());

        $this->keyGenerator = $keyGenerator ?? new KeyGenerator();
    }

    /**
     * @return array<string, string>
     */
    public function persist(Entity $objectMapper, $object): ObjectToPersist
    {
        $key = $this->keyGenerator->generateKey($objectMapper, $object);

        return new ObjectToPersist(
            persisterClass: get_class($objectMapper->persister),
            operation: PersisterOperations::OPERATION_PERSIST->value,
            redisKey: $key,
            value: $objectMapper->converter->convert(data: $object)
        );
    }

    /**
     * @return array<string, string>
     */
    public function delete(Entity $objectMapper, $object): ObjectToPersist
    {
        $identifier = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $key = sprintf('%s:%s', $objectMapper->prefix ?: get_class($object), $object->{$identifier->getName()});

        return new ObjectToPersist(
            persisterClass: get_class($objectMapper->persister),
            operation: PersisterOperations::OPERATION_DELETE->value,
            redisKey: $key,
        );
    }

    abstract public function doPersist(string $key, array|\stdClass $data): void;

    abstract public function doDelete(string $key): void;
}
