<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository;

use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\ConverterInterface;
use Talleu\RedisOm\Om\RedisFormat;

abstract class AbstractObjectRepository implements RepositoryInterface
{
    public ?string $prefix = null;
    public ?string $className = null;
    protected ?RedisClientInterface $redisClient = null;
    protected ?ConverterInterface $converter = null;

    public function __construct(public ?string $format = null)
    {
    }

    abstract public function find($identifier): ?object;

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $data = $this->redisClient->search($this->prefix, $criteria, $orderBy ?? [], $this->format, $limit);

        $collection = [];
        foreach ($data as $item) {
            $collection[] = $this->converter->revert($item, $this->className);
        }

        return $collection;
    }

    public function findLike(string $search, ?int $limit = null): array
    {
        $data = $this->redisClient->searchLike($this->prefix, $search, $this->format, $limit);

        $collection = [];
        foreach ($data as $item) {
            $collection[] = $this->converter->revert($item, $this->className);
        }

        return $collection;
    }

    public function findAll(): array
    {
        return $this->findBy([]);
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        $data = $this->redisClient->search($this->prefix, $criteria, $orderBy ?? [], $this->format, 1);

        if ($data === []) {
            return null;
        }

        return $this->converter->revert($data[0], $this->className);
    }

    public function count(array $criteria = []): int
    {
        return $this->redisClient->count($this->prefix, $criteria);
    }

    public function setRedisClient(RedisClientInterface $redisClient): void
    {
        $this->redisClient = $redisClient;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    public function setConverter(?ConverterInterface $converter): void
    {
        $this->converter = $converter;
    }

    public function setFormat(?string $format = null): void
    {
        $this->format = $format ?? RedisFormat::HASH->value;
    }
}
