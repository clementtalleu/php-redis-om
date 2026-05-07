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
use Talleu\RedisOm\Exception\UniqueConstraintViolationException;
use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Metadata\ClassMetadata;
use Talleu\RedisOm\Om\Metadata\MetadataFactory;
use Talleu\RedisOm\Om\Persister\HashModel\HashPersister;
use Talleu\RedisOm\Om\Persister\JsonModel\JsonPersister;
use Talleu\RedisOm\Om\Persister\ObjectToPersist;
use Talleu\RedisOm\Om\Persister\PersisterInterface;
use Talleu\RedisOm\Om\Persister\PersisterOperations;
use Talleu\RedisOm\Om\Repository\RepositoryInterface;

final class RedisObjectManager implements RedisObjectManagerInterface
{
    /** @var PersisterInterface[] */
    protected array $persisters = [];

    /** @var array<string, array<string, ObjectToPersist[]>> */
    protected array $objectsToFlush = [];

    /** @var array<string, Entity> */
    private array $entityMapperCache = [];

    /** @var array<string, ClassMetadata> */
    private array $classMetadataCache = [];

    /** @var array<string, array<string|int, object>> Identity map: className -> id -> object */
    private array $identityMap = [];

    /** @var array<string, array> Snapshot of converted data at load time: "class:id" -> converted array */
    private array $snapshots = [];

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

        // Register in identity map
        $identifier = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $id = $object->{$identifier->getName()};
        if ($id !== null) {
            $this->identityMap[get_class($object)][$id] = $object;
        }

        $this->eventManager->dispatchEvent(
            Events::POST_PERSIST,
            new LifecycleEventArgs($object, $this)
        );
    }

    /**
     * @inheritdoc
     */
    public function merge(object $object): void
    {
        $objectMapper = $this->getEntityMapper($object);
        $identifierProperty = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $id = $object->{$identifierProperty->getName()};
        $className = get_class($object);
        $snapshotKey = $className . ':' . $id;

        $currentData = $objectMapper->converter->convert($object);

        // If we have a snapshot, compute diff
        if (isset($this->snapshots[$snapshotKey])) {
            $changedFields = [];
            foreach ($currentData as $field => $value) {
                if (!array_key_exists($field, $this->snapshots[$snapshotKey]) || $this->snapshots[$snapshotKey][$field] !== $value) {
                    $changedFields[$field] = $value;
                }
            }

            if ($changedFields === []) {
                return; // Nothing changed
            }
        } else {
            // No snapshot = full persist (new object or not loaded via find)
            $this->persist($object);
            return;
        }

        $persister = $this->registerPersister($objectMapper);
        $key = sprintf('%s:%s', $objectMapper->prefix ?: $className, $id);

        $objectToMerge = new ObjectToPersist(
            persisterClass: get_class($persister),
            operation: PersisterOperations::OPERATION_MERGE->value,
            redisKey: $key,
            converter: $objectMapper->converter,
            value: $object,
            changedFields: $changedFields,
            previousValues: $this->snapshots[$snapshotKey],
        );

        $this->objectsToFlush[$objectToMerge->persisterClass][$objectToMerge->operation][$objectToMerge->redisKey] = $objectToMerge;

        // Update snapshot
        $this->snapshots[$snapshotKey] = $currentData;
        $this->identityMap[$className][$id] = $object;
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

        // Remove from identity map
        $identifier = $this->keyGenerator->getIdentifier(new \ReflectionClass($object));
        $id = $object->{$identifier->getName()};
        unset($this->identityMap[get_class($object)][$id]);

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
        if ($this->objectsToFlush === []) {
            return;
        }

        $uniquePlan = $this->buildUniqueConstraintPlan();

        if ($uniquePlan === null) {
            $this->redisClient->multi();
            try {
                $this->doFlushOperations();
                $this->redisClient->exec();
            } catch (\Throwable $e) {
                $this->redisClient->discard();
                throw $e;
            }
            return;
        }

        $this->redisClient->watch(...array_keys($uniquePlan['watch']));

        foreach ($uniquePlan['checks'] as $uniqueKey => $meta) {
            $existingId = $this->redisClient->get($uniqueKey);
            if ($existingId !== null && $existingId !== $meta['allowedId']) {
                $this->redisClient->unwatch();
                throw UniqueConstraintViolationException::forFields($meta['class'], $meta['fields'], $meta['values']);
            }
        }

        $this->redisClient->multi();
        try {
            $this->doFlushOperations();
            foreach ($uniquePlan['sets'] as $uniqueKey => $id) {
                $this->redisClient->set($uniqueKey, $id);
            }
            foreach ($uniquePlan['dels'] as $uniqueKey) {
                $this->redisClient->del($uniqueKey);
            }
            $committed = $this->redisClient->exec();
        } catch (\Throwable $e) {
            $this->redisClient->discard();
            throw $e;
        }

        if (!$committed) {
            throw UniqueConstraintViolationException::concurrentModification();
        }
    }

    private function doFlushOperations(): void
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
     * @return array{watch: array<string,true>, checks: array<string,array>, sets: array<string,string>, dels: list<string>}|null
     */
    private function buildUniqueConstraintPlan(): ?array
    {
        $watch = [];
        $checks = [];
        $sets = [];
        $dels = [];

        foreach ($this->objectsToFlush as $objectsByOperation) {
            foreach ($objectsByOperation as $operation => $objectsToPersist) {
                foreach ($objectsToPersist as $objectToPersist) {
                    $object = $objectToPersist->value;
                    $className = get_class($object);
                    $metadata = $this->getCachedClassMetadata($className);

                    if (!$metadata->hasUniqueConstraints()) {
                        continue;
                    }

                    $idFieldName = $metadata->identifier[0];
                    $currentId = (string) (new \ReflectionProperty($className, $idFieldName))->getValue($object);

                    foreach ($metadata->uniqueConstraints as $fields) {
                        sort($fields);

                        $currentValues = [];
                        foreach ($fields as $field) {
                            $currentValues[$field] = (string) (new \ReflectionProperty($className, $field))->getValue($object);
                        }

                        $fieldKey = implode(',', $fields);
                        $uniqueKey = sprintf('unique:%s:%s:%s', $className, $fieldKey, implode(':', array_values($currentValues)));

                        if ($operation === PersisterOperations::OPERATION_DELETE->value) {
                            $watch[$uniqueKey] = true;
                            $dels[] = $uniqueKey;
                            continue;
                        }

                        if ($operation === PersisterOperations::OPERATION_MERGE->value) {
                            $oldValues = [];
                            $anyChanged = false;
                            foreach ($fields as $field) {
                                $oldVal = isset($objectToPersist->previousValues[$field])
                                    ? (string) $objectToPersist->previousValues[$field]
                                    : null;
                                $oldValues[$field] = $oldVal;
                                if ($oldVal !== $currentValues[$field]) {
                                    $anyChanged = true;
                                }
                            }

                            if ($anyChanged && !in_array(null, $oldValues, true)) {
                                $oldUniqueKey = sprintf('unique:%s:%s:%s', $className, $fieldKey, implode(':', array_values($oldValues)));
                                $watch[$oldUniqueKey] = true;
                                $dels[] = $oldUniqueKey;
                                $watch[$uniqueKey] = true;
                                $checks[$uniqueKey] = ['allowedId' => $currentId, 'class' => $className, 'fields' => $fields, 'values' => array_values($currentValues)];
                                $sets[$uniqueKey] = $currentId;
                            }
                            continue;
                        }

                        if (isset($sets[$uniqueKey]) && $sets[$uniqueKey] !== $currentId) {
                            throw UniqueConstraintViolationException::forFields($className, $fields, array_values($currentValues));
                        }

                        $watch[$uniqueKey] = true;
                        $checks[$uniqueKey] = ['allowedId' => $currentId, 'class' => $className, 'fields' => $fields, 'values' => array_values($currentValues)];
                        $sets[$uniqueKey] = $currentId;
                    }
                }
            }
        }

        if ($watch === []) {
            return null;
        }

        return ['watch' => $watch, 'checks' => $checks, 'sets' => $sets, 'dels' => $dels];
    }

    private function getCachedClassMetadata(string $className): ClassMetadata
    {
        return $this->classMetadataCache[$className] ??= (new MetadataFactory())->createClassMetadata($className);
    }

    /**
     * @inheritdoc
     */
    public function find(string $className, $id): ?object
    {
        // Check identity map first
        if (isset($this->identityMap[$className][$id])) {
            return $this->identityMap[$className][$id];
        }

        $objectMapper = $this->getEntityMapper($className);
        $object = $objectMapper->repository->find((string)$id);

        // Store in identity map and take snapshot for dirty tracking
        if ($object !== null) {
            $this->identityMap[$className][$id] = $object;
            $snapshotKey = $className . ':' . $id;
            $this->snapshots[$snapshotKey] = $objectMapper->converter->convert($object);
        }

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function clear(): void
    {
        $this->objectsToFlush = [];
        $this->identityMap = [];
        $this->snapshots = [];
    }

    /**
     * @inheritdoc
     */
    public function stream(string $className, array $criteria = [], int $batchSize = 100): \Generator
    {
        $offset = 0;
        do {
            $batch = $this->getRepository($className)->findBy($criteria, null, $batchSize, $offset);
            foreach ($batch as $object) {
                yield $object;
            }
            $this->clear();
            $offset += $batchSize;
        } while (count($batch) === $batchSize);
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
        if (!array_key_exists($persisterClassName, $this->objectsToFlush)) {
            return;
        }
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
        if (!array_key_exists($persisterClassName, $this->objectsToFlush)) {
            return false;
        }
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
        $className = is_string($object) ? $object : get_class($object);

        if (array_key_exists($className, $this->entityMapperCache)) {
            return $this->entityMapperCache[$className];
        }

        $reflectionClass = new \ReflectionClass($className);
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

        $this->entityMapperCache[$className] = $redisEntity;

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
