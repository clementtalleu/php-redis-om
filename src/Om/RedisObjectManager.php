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

    /** @var array<string, ObjectToPersist> */
    protected array $objectsToFlush = [];
    protected ?KeyGenerator $keyGenerator = null;

    public function __construct(private readonly ?bool $createPersistentConnection = null)
    {
        $this->keyGenerator = new KeyGenerator();
    }

    public function persist(object $object): void
    {
        $objectMapper = $this->getEntityMapper($object);
        $persister = $this->registerPersister($objectMapper, $object);

        $objectToPersist = $persister->persist($objectMapper, $object);
        $this->objectsToFlush[$objectToPersist->redisKey] = $objectToPersist;
    }

    public function remove(object $object): void
    {
        $objectMapper = $this->getEntityMapper($object);
        $persister = $this->registerPersister($objectMapper, $object);

        $objectToRemove = $persister->delete($objectMapper, $object);
        $this->objectsToFlush[$objectToRemove->redisKey] = $persister->delete($objectMapper, $object);
    }

    public function flush(): void
    {
        foreach ($this->objectsToFlush as $key => $object) {
            $this->persisters[$object->persisterClass]->{$object->operation}($key, $object->value);
            unset($this->objectsToFlush[$key]);
        }
    }

    public function find(string $className, $id): ?object
    {
        $objectMapper = $this->getEntityMapper($className);

        return $objectMapper->repository->find((string) $id);
    }

    public function clear(): void
    {
        $this->objectsToFlush = [];
    }

    public function detach(object $object): void
    {
        $identifier = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $key = sprintf("%s:%s", $this->getEntityMapper($object)->prefix ?: get_class($object), $object->{$identifier->getName()});

        foreach ($this->objectsToFlush as $redisKey => $objectToFlush) {
            if ($key === $redisKey) {
                unset($this->objectsToFlush[$redisKey]);
            }
        }
    }

    public function refresh(object $object): object
    {
        $objectMapper = $this->getEntityMapper($object);
        $identifierProperty = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));

        return $objectMapper->repository->find($objectMapper->prefix.':'.$object->{$identifierProperty->getName()});
    }

    public function getRepository(string $className): RepositoryInterface
    {
        $objectMapper = $this->getEntityMapper($className);

        return $objectMapper->repository;
    }

    public function getClassMetadata(string $className): ClassMetadata
    {
        return (new MetadataFactory())->createClassMetadata($className);
    }

    public function getMetadataFactory()
    {
        return new MetadataFactory();
    }

    public function initializeObject(object $obj)
    {
        return new $obj();
    }

    public function contains(object $object): bool
    {
        $objectMapper = $this->getEntityMapper($object);
        $identifierProperty = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        foreach ($this->objectsToFlush as $objectToFlush) {
            if ($objectToFlush->redisKey === $objectMapper->prefix.':'.$object->{$identifierProperty->getName()}) {
                return true;
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
        if (($persister = $redisEntity->persister) === null) {
            throw new RedisOmInvalidArgumentException(sprintf("No persister found for %s object.", get_class($object)));
        }

        $persisterClass = get_class($persister);
        if (!array_key_exists($persisterClass, $this->persisters)) {
            $this->persisters[$persisterClass] = $persister;
        }

        return $persister;
    }
}
