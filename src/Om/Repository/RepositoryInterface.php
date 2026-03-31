<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository;

use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\ConverterInterface;
use Talleu\RedisOm\Om\Paginator;
use Talleu\RedisOm\Om\QueryBuilder;

/**
 * @template T of object
 */
interface RepositoryInterface
{
    /**
     * Find an object by its identifier.
     * @return T|null
     */
    public function find($identifier): ?object;

    /**
     * Get a specific property value from an object by its identifier.
     */
    public function getPropertyValue($identifier, string $property): mixed;

    /**
     * Find multiple objects by their identifiers using pipeline.
     * @param array<int|string> $identifiers
     * @return T[]
     */
    public function findMultiple(array $identifiers): array;

    /**
     * Find objects by a set of criteria.
     * Supports range filters: ['age' => ['$gte' => 18, '$lte' => 65]]
     * @param array<string, mixed> $criteria as ['property' => 'value'] or range operators
     * @param array<string, string>|null $orderBy as ['property' => 'ASC|DESC']
     * @return T[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find objects whose properties contain a given value (case insensitive, partial match).
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return T[]
     */
    public function findByLike(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = 0): array;

    /**
     * Find objects whose properties start with a given value.
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return T[]
     */
    public function findByStartWith(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = 0): array;

    /**
     * Find objects whose properties end with a given value.
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return T[]
     */
    public function findByEndWith(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = 0): array;

    /**
     * Find objects by a full text search.
     * @return T[]
     */
    public function findLike(string $search, ?int $limit = null): array;

    /**
     * Find all objects from specific class.
     * @return iterable<T>
     */
    public function findAll(): iterable;

    /**
     * Find one object by a set of criteria.
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return T|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * Find an object whose properties contain a given value (case insensitive, partial match).
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return T|null
     */
    public function findOneByLike(array $criteria, ?array $orderBy = null): ?object;

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
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria = []): int;

    /**
     * Count objects by a set of criteria with a "LIKE" strategy.
     * @param array<string, mixed> $criteria
     */
    public function countByLike(array $criteria = []): int;

    /**
     * Paginate results with total count.
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return Paginator<T>
     */
    public function paginate(array $criteria = [], int $page = 1, int $itemsPerPage = 20, ?array $orderBy = null): Paginator;

    /**
     * Find objects within a geographic radius.
     * @param string $geoField The GEO-indexed property name
     * @param float $longitude Center longitude
     * @param float $latitude Center latitude
     * @param float $radius Radius value
     * @param string $unit Distance unit: km, m, mi, ft
     * @return T[]
     */
    public function findByGeoRadius(string $geoField, float $longitude, float $latitude, float $radius, string $unit = 'km', ?int $limit = null): array;

    /**
     * Create a new QueryBuilder instance.
     */
    public function createQueryBuilder(): QueryBuilder;
}
