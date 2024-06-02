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
    public function persist(Entity $objectMapper, $object): array;

    /**
     * Persists the object to redis.
     */
    public function doPersist(string $key, array|\stdClass $data): void;

    /**
     * Request object deletion.
     */
    public function delete(Entity $objectMapper, $object): array;

    /**
     * Deletes an object from redis.
     */
    public function doDelete(string $key): void;
}
