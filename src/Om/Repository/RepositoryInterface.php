<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository;

use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\ConverterInterface;

interface RepositoryInterface
{
    public function find($identifier): ?object;
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    public function findAll(): array;
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;
    public function setRedisClient(RedisClientInterface $redisClient): void;
    public function setPrefix(string $prefix): void;
    public function setClassName(string $className): void;
    public function setConverter(?ConverterInterface $converter): void;
    public function setFormat(string $format): void;
    public function count(array $criteria = []): int;
}
