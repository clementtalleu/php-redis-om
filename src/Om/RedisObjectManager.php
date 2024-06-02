<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om;

use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Exception\RedisOmInvalidArgumentException;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Converters\JsonModel\JsonObjectConverter;
use Talleu\RedisOm\Om\Key\KeyGenerator;
use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Persister\AbstractPersister;
use Talleu\RedisOm\Om\Persister\PersisterInterface;
use Talleu\RedisOm\Om\Repository\RepositoryInterface;

class RedisObjectManager implements RedisObjectManagerInterface
{
    /** @var PersisterInterface[] */
    protected array $persisters = [];
    protected array $objectsToFlush = [];
    protected ?KeyGenerator $keyGenerator = null;

    public function __construct(
        private ?array $options = [],
    ) {
        $this->keyGenerator = new KeyGenerator();
    }

    public function persist(object $object): void
    {
        $objectMapper = $this->getEntityMapper($object);
        $persister = $this->registerPersister($objectMapper, $object);

        $this->objectsToFlush[] = $persister->persist($objectMapper, $object);
    }

    public function remove(object $object)
    {
        $objectMapper = $this->getEntityMapper($object);
        $persister = $this->registerPersister($objectMapper, $object);
        $this->objectsToFlush[] = $persister->delete($objectMapper, $object);
    }

    public function flush(): void
    {
        foreach ($this->objectsToFlush as $key => $object) {
            $this->persisters[$object[AbstractPersister::PERSISTER_KEY_NAME]]->{$object['operation']}($object['key'], $object['value']);
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

    public function detach(object $object, ?string $keyObject = null): void
    {
        foreach ($this->objectsToFlush as $key => $objectToFlush) {
            if ($objectToFlush['key'] === $keyObject) {
                unset($this->objectsToFlush[$key]);
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

    public function getClassMetadata(string $className)
    {
        // TODO: Implement getClassMetadata() method.
    }

    public function getMetadataFactory()
    {
        // TODO: Implement getMetadataFactory() method.
    }

    public function initializeObject(object $obj)
    {
        // TODO: Implement initializeObject() method.
    }

    public function contains(object $object)
    {
        $objectMapper = $this->getEntityMapper($object);
        $identifierProperty = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        foreach ($this->objectsToFlush as $objectToFlush) {
            if ($objectToFlush['key'] === $objectMapper->prefix.':'.$object->{$identifierProperty->getName()}) {
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
        $redisEntity->options = $this->options;

        $redisEntity->repository->setPrefix($redisEntity->prefix ?? $reflectionClass->getName());
        $redisEntity->repository->setClassName($reflectionClass->getName());
        $redisEntity->repository->setConverter($redisEntity->converter ?? ($redisEntity->format === RedisFormat::HASH->value ? new HashObjectConverter() : new JsonObjectConverter()));
        $redisEntity->repository->setRedisClient($redisEntity->redisClient ?? (new RedisClient($this->options)));
        $redisEntity->repository->setFormat($redisEntity->format);

        return $redisEntity;
    }

    protected function registerPersister(Entity $redisEntity, object $object): PersisterInterface
    {
        if (($persister = $redisEntity->persister) === null) {
            throw new RedisOmInvalidArgumentException(sprintf('No persister found for %s object.', get_class($object)));
        }

        $persisterClass = get_class($persister);
        if (!array_key_exists($persisterClass, $this->persisters)) {
            $this->persisters[$persisterClass] = $persister;
        }

        return $persister;
    }
}
