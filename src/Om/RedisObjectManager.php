<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om;

use Talleu\RedisOm\Client\PredisClient;
use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Event\EventManager;
use Talleu\RedisOm\Event\EventManagerInterface;
use Talleu\RedisOm\Event\Events;
use Talleu\RedisOm\Event\LifecycleEventArgs;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Converters\JsonModel\JsonObjectConverter;
use Talleu\RedisOm\Om\Key\KeyGenerator;
use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Metadata\ClassMetadata;
use Talleu\RedisOm\Om\Metadata\MetadataFactory;
use Talleu\RedisOm\Om\Persister\HashModel\HashPersister;
use Talleu\RedisOm\Om\Persister\JsonModel\JsonPersister;
use Talleu\RedisOm\Om\Persister\ObjectToPersist;
use Talleu\RedisOm\Om\Persister\PersisterInterface;
use Talleu\RedisOm\Om\Repository\RepositoryInterface;

final class RedisObjectManager implements RedisObjectManagerInterface
{
    /** @var PersisterInterface[] */
    protected array $persisters = [];

    /** @var array<string, array<string, ObjectToPersist[]>> */
    protected array $objectsToFlush = [];

    protected ?KeyGenerator $keyGenerator = null;
    private RedisClientInterface $redisClient;
    private EventManagerInterface $eventManager;

    public function __construct(
        ?RedisClientInterface $redisClient = null,
        ?EventManagerInterface $eventManager = null,
    ) {
        $this->redisClient = $redisClient ?? (getenv('REDIS_CLIENT') === 'predis' ? new PredisClient() : new RedisClient());
        $this->eventManager = $eventManager ?? new EventManager();
        $this->keyGenerator = new KeyGenerator();
    }

    /**
     * @inheritdoc
     */
    public function persist(object $object): void
    {
        $this->eventManager->dispatchEvent(
            Events::PRE_PERSIST,
            new LifecycleEventArgs($object, $this)
        );

        $objectMapper = $this->getEntityMapper($object);
        $persister = $this->registerPersister($objectMapper);

        $objectToPersist = $persister->persist(objectMapper: $objectMapper, object: $object);
        $this->objectsToFlush[$objectToPersist->persisterClass][$objectToPersist->operation][$objectToPersist->redisKey] = $objectToPersist;

        $this->eventManager->dispatchEvent(
            Events::POST_PERSIST,
            new LifecycleEventArgs($object, $this)
        );
    }

    /**
     * @inheritdoc
     */
    public function remove(object $object): void
    {
        $this->eventManager->dispatchEvent(
            Events::PRE_REMOVE,
            new LifecycleEventArgs($object, $this)
        );

        $objectMapper = $this->getEntityMapper($object);
        $persister = $this->registerPersister($objectMapper);

        $objectToRemove = $persister->delete($objectMapper, $object);
        $this->objectsToFlush[$objectToRemove->persisterClass][$objectToRemove->operation][$objectToRemove->redisKey] = $objectToRemove;

        $this->eventManager->dispatchEvent(
            Events::POST_REMOVE,
            new LifecycleEventArgs($object, $this)
        );
    }

    /**
     * @inheritdoc
     */
    public function flush(): void
    {
        foreach ($this->objectsToFlush as $persisterClassName => $objectsByOperation) {
            foreach ($objectsByOperation as $operation => $objectToPersists) {
                $this->persisters[$persisterClassName]->{$operation}($objectToPersists);
                foreach ($objectToPersists as $objectToPersist) {
                    $this->eventManager->dispatchEvent(
                        Events::POST_FLUSH,
                        new LifecycleEventArgs($objectToPersist->value, $this)
                    );
                }
                unset($this->objectsToFlush[$persisterClassName][$operation]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function find(string $className, $id): ?object
    {
        $objectMapper = $this->getEntityMapper($className);

        return $objectMapper->repository->find((string)$id);
    }

    /**
     * @inheritdoc
     */
    public function clear(): void
    {
        $this->objectsToFlush = [];
    }

    /**
     * @inheritdoc
     */
    public function detach(object $object): void
    {
        $identifier = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $objectMapper = $this->getEntityMapper($object);
        $key = sprintf('%s:%s', $objectMapper->prefix ?: get_class($object), $object->{$identifier->getName()});

        $persisterClassName = get_class($this->registerPersister($objectMapper));
        foreach ($this->objectsToFlush[$persisterClassName] as $operation => $objectsToFlush) {
            foreach ($objectsToFlush as $redisKey => $objectToFlush) {
                if ($redisKey === $key) {
                    unset($this->objectsToFlush[$persisterClassName][$operation][$key]);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function refresh(object $object): object
    {
        $objectMapper = $this->getEntityMapper($object);
        $identifierProperty = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));

        return $objectMapper->repository->find($objectMapper->prefix . ':' . $object->{$identifierProperty->getName()});
    }

    /**
     * @inheritdoc
     */
    public function getRepository(string $className): RepositoryInterface
    {
        $objectMapper = $this->getEntityMapper($className);

        return $objectMapper->repository;
    }

    /**
     * @inheritdoc
     */
    public function getClassMetadata(string $className): ClassMetadata
    {
        return (new MetadataFactory())->createClassMetadata($className);
    }

    /**
     * @inheritdoc
     */
    public function getMetadataFactory()
    {
        return new MetadataFactory();
    }

    /**
     * @inheritdoc
     */
    public function initializeObject(object $obj)
    {
        return new $obj();
    }

    /**
     * @inheritdoc
     */
    public function contains(object $object): bool
    {
        $identifier = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $objectMapper = $this->getEntityMapper($object);
        $key = sprintf('%s:%s', $objectMapper->prefix ?: get_class($object), $object->{$identifier->getName()});
        $persisterClassName = get_class($this->registerPersister($objectMapper));
        foreach ($this->objectsToFlush[$persisterClassName] as $operation => $objectsToFlush) {
            foreach ($objectsToFlush as $redisKey => $objectToFlush) {
                if ($redisKey === $key) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getEntityMapper(string|object $object): Entity
    {
        $reflectionClass = new \ReflectionClass($object);
        $attributes = $reflectionClass->getAttributes(Entity::class);
        if ($attributes === []) {
            throw new \InvalidArgumentException('The object must be annotated with #[RedisOm\Entity] attribute');
        }

        /** @var Entity $redisEntity */
        $redisEntity = $attributes[0]->newInstance();

        $redisEntity->repository->setPrefix($redisEntity->prefix ?? $reflectionClass->getName());
        $redisEntity->repository->setClassName($reflectionClass->getName());
        $redisEntity->repository->setConverter($redisEntity->converter ?? ($redisEntity->format === RedisFormat::HASH->value ? new HashObjectConverter() : new JsonObjectConverter()));

        $redisEntity->repository->setRedisClient($this->redisClient);
        $redisEntity->repository->setFormat($redisEntity->format);

        return $redisEntity;
    }

    protected function registerPersister(Entity $object): PersisterInterface
    {
        $persister = ($object->format === RedisFormat::JSON->value ? new JsonPersister(redis: $this->redisClient) : new HashPersister(redis: $this->redisClient));

        $persisterClass = $persister::class;
        if (!array_key_exists($persisterClass, $this->persisters)) {
            $this->persisters[$persisterClass] = $persister;
        }
        return $persister;
    }

    public function createIndex(object $object, ?string $fqcn = null, ?array $propertiesToIndex = []): void
    {
        $this->redisClient->createIndex($object->prefix ?? $fqcn, $object->format ?? RedisFormat::HASH->value, $propertiesToIndex);
    }

    public function dropIndex(object $object, ?string $fqcn = null): void
    {
        $this->redisClient->dropIndex($object->prefix ?? $fqcn);
    }

    /**
     * @inheritdoc
     */
    public function getExpirationTime(object $object): ?\DateTimeImmutable
    {
        $identifier = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $objectMapper = $this->getEntityMapper($object);
        $key = sprintf('%s:%s', $objectMapper->prefix ?: get_class($object), $object->{$identifier->getName()});

        $timestamp = $this->redisClient->expireTime($key);

        if (-1 === $timestamp) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($timestamp);
    }

    public function getRedisClient(): ?RedisClientInterface
    {
        return $this->redisClient;
    }

    public function getEventManager(): EventManagerInterface
    {
        return $this->eventManager;
    }
}
