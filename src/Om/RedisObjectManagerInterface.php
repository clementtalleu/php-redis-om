<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om;

use Talleu\RedisOm\Om\Repository\RepositoryInterface;

interface RedisObjectManagerInterface
{
    /**
     * Request object persistence. The object will be persisted in the next flush.
     */
    public function persist(object $object): void;

    /**
     * Request object deletion. The object will be deleted in the next flush.
     */
    public function remove(object $object): void;

    /**
     * Merge an object: only persist changed properties (partial update).
     * The object must have been previously loaded via find().
     */
    public function merge(object $object): void;

    /**
     * Get object by class name (FQCN) and id.
     * @template T of object
     * @param class-string<T> $className
     * @return T|null
     */
    public function find(string $className, $id): ?object;

    /**
     * Clear all objects from the current unit of work. Nothing will be persisted, nor deleted on flush.
     */
    public function clear(): void;

    /**
     * Iterate over objects of a class, clearing the identity map after each batch.
     * Keeps memory bounded for long-running jobs. Pending unflushed operations are discarded
     * at each batch boundary — call flush() before streaming if you have queued writes.
     * Objects from a previous batch are detached once the next batch starts; merge() on them
     * falls back to a full persist.
     * @template T of object
     * @param class-string<T> $className
     * @param array<string, mixed> $criteria
     * @return \Generator<int, T>
     */
    public function stream(string $className, array $criteria = [], int $batchSize = 100): \Generator;

    /**
     * Detach an object from the current unit of work, it will not be persisted nor deleted on flush.
     */
    public function detach(object $object): void;

    /**
     * Refresh an object from the current unit of work, it will be reloaded from the redis datastore.
     */
    public function refresh(object $object): object;

    /**
     * Flush all pending operations (persist, remove) to the redis datastore.
     */
    public function flush(): void;

    /**
     * Get the repository for a given class name.
     * @template T of object
     * @param class-string<T> $className
     * @return RepositoryInterface<T>
     */
    public function getRepository(string $className): RepositoryInterface;

    /**
     * Get all metadata from a mapped entity by class name.
     */
    public function getClassMetadata(string $className);

    /**
     * Get the metadata factory.
     */
    public function getMetadataFactory();

    /**
     * Create new instance of the object.
     */
    public function initializeObject(object $obj);

    /**
     * Check if the object is managed by the current unit of work.
     */
    public function contains(object $object): bool;

    public function createIndex(object $object, ?string $fqcn = null, ?array $propertiesToIndex = []): void;

    public function dropIndex(object $object, ?string $fqcn = null): void;

    /**
     * Get the expiration datetime of an object
     */
    public function getExpirationTime(object $object): ?\DateTimeImmutable;
}
