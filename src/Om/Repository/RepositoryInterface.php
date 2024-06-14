<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository;

use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\ConverterInterface;

interface RepositoryInterface
{
    /**
     * Find an object by its identifier.
     */
    public function find($identifier): ?object;

    /**
     * Get a specific property value from an object by its identifier.
     */
    public function getPropertyValue($identifier, string $property): mixed;

    /**
     * Find objects by a set of criteria.
     * @param array $criteria as ['property' => 'value']
     * @param array|null $orderBy as ['property' => 'ASC|DESC']
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find objects by a full text search.
     */
    public function findLike(string $search, ?int $limit = null): array;

    /**
     * Find all objects from specific class.
     */
    public function findAll(): array;

    /**
     * Find one object by a set of criteria.
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * Set a given redis client to the repository.
     */
    public function setRedisClient(RedisClientInterface $redisClient): void;

    /**
     * Set the repository prefix.
     */
    public function setPrefix(string $prefix): void;

    public function setClassName(string $className): void;
    public function setConverter(?ConverterInterface $converter): void;
    public function setFormat(string $format): void;

    /**
     * Count objects by a set of criteria.
     */
    public function count(array $criteria = []): int;
}
