<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister;

use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Key\KeyGenerator;
use Talleu\RedisOm\Om\Mapping\Entity;

abstract class AbstractPersister implements PersisterInterface
{
    public const OPERATION_PERSIST = 'doPersist';
    public const OPERATION_DELETE = 'doDelete';
    public const OPERATION_KEY_NAME = 'operation';
    public const PERSISTER_KEY_NAME = 'persister';

    protected RedisClientInterface $redis;
    private KeyGenerator $keyGenerator;

    public function __construct(?array $options = null)
    {
        $this->redis = (new RedisClient($options));

        $this->keyGenerator = new KeyGenerator();
    }

    /**
     * @return array<string, string>
     */
    public function persist(Entity $objectMapper, $object): array
    {
        $key = $this->keyGenerator->generateKey($objectMapper, $object);

        return [
            static::PERSISTER_KEY_NAME => get_class($objectMapper->persister),
            static::OPERATION_KEY_NAME => static::OPERATION_PERSIST,
            'key' => $key,
            'value' => $objectMapper->converter->convert(data: $object)
        ];
    }

    /**
     * @return array<string, string>
     */
    public function delete(Entity $objectMapper, $object): array
    {
        $identifier = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $key = sprintf('%s:%s', $objectMapper->prefix ?: get_class($object), $object->{$identifier->getName()});

        return [
            static::PERSISTER_KEY_NAME => get_class($objectMapper->persister),
            static::OPERATION_KEY_NAME => static::OPERATION_DELETE,
            'key' => $key
        ];
    }

    abstract public function doPersist(string $key, array|\stdClass $data): void;

    abstract public function doDelete(string $key): void;
}
