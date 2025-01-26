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
     * Get object by class name (FQCN) and id.
     */
    public function find(string $className, $id): ?object;

    /**
     * Clear all objects from the current unit of work. Nothing will be persisted, nor deleted on flush.
     */
    public function clear(): void;

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

    public function createIndex(object $object): void;

    public function dropIndex(object $object): void;

    /**
     * Get the expiration datetime of an object
     */
    public function getExpirationTime(object $object): ?\DateTimeImmutable;
}
