<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om;

use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Exception\RedisOmInvalidArgumentException;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Converters\JsonModel\JsonObjectConverter;
use Talleu\RedisOm\Om\Key\KeyGenerator;
use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Metadata\ClassMetadata;
use Talleu\RedisOm\Om\Metadata\MetadataFactory;
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

    public function __construct(private readonly ?bool $createPersistentConnection = null)
    {
        $this->keyGenerator = new KeyGenerator();
    }

    /**
     * @inheritdoc
     */
    public function persist(object $object): void
    {
        $objectMapper = $this->getEntityMapper($object);
        $persister = $this->registerPersister($objectMapper, $object);

        $objectToPersist = $persister->persist($objectMapper, $object);
        $this->objectsToFlush[$objectToPersist->persisterClass][$objectToPersist->operation][$objectToPersist->redisKey] = $objectToPersist;
    }

    /**
     * @inheritdoc
     */
    public function remove(object $object): void
    {
        $objectMapper = $this->getEntityMapper($object);
        $persister = $this->registerPersister($objectMapper, $object);

        $objectToRemove = $persister->delete($objectMapper, $object);
        $this->objectsToFlush[$objectToRemove->persisterClass][$objectToRemove->operation][$objectToRemove->redisKey] = $objectToRemove;
    }

    /**
     * @inheritdoc
     */
    public function flush(): void
    {
        foreach ($this->objectsToFlush as $persisterClassName => $objectsByOperation) {
            foreach ($objectsByOperation as $operation => $objectToPersists) {
                $this->persisters[$persisterClassName]->{$operation}($objectToPersists);
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

        return $objectMapper->repository->find((string) $id);
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

        $persisterClassName = get_class($objectMapper->persister);
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

        return $objectMapper->repository->find($objectMapper->prefix.':'.$object->{$identifierProperty->getName()});
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
        $persisterClassName = get_class($objectMapper->persister);
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

        $redisClient = $redisEntity->redisClient ?? new RedisClient();
        if ($this->createPersistentConnection === true) {
            $redisClient->createPersistentConnection();
        }
        $redisEntity->repository->setRedisClient($redisClient);
        $redisEntity->repository->setFormat($redisEntity->format);

        return $redisEntity;
    }

    protected function registerPersister(Entity $redisEntity, object $object): PersisterInterface
    {
        if (is_null($persister = $redisEntity->persister)) {
            throw new RedisOmInvalidArgumentException(sprintf('No persister found for %s object.', get_class($object)));
        }

        $persisterClass = get_class($persister);
        if (!array_key_exists($persisterClass, $this->persisters)) {
            $this->persisters[$persisterClass] = $persister;
        }

        return $persister;
    }
}
