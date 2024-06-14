<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister;

use Talleu\RedisOm\Om\Mapping\Entity;

/**
 * Object persister interface
 */
interface PersisterInterface
{
    /**
     * Request object persistence.
     */
    public function persist(Entity $objectMapper, $object): ObjectToPersist;

    /**
     * @param ObjectToPersist[] $objectsToPersist
     * Persists the object to redis.
     */
    public function doPersist(array $objectsToPersist): void;

    /**
     * Request object deletion.
     */
    public function delete(Entity $objectMapper, $object): ObjectToPersist;

    /**
     * @param ObjectToPersist[] $objectsToRemove
     * Deletes an object from redis.
     */
    public function doDelete(array $objectsToRemove): void;
}
