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

    public function clear(): void;

    public function detach(object $object): void;

    public function refresh(object $object): object;

    public function flush(): void;

    public function getRepository(string $className): RepositoryInterface;

    public function getClassMetadata(string $className);

    public function getMetadataFactory();

    public function initializeObject(object $obj);

    public function contains(object $object): bool;
}
