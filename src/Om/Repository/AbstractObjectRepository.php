<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository;

use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;
use Talleu\RedisOm\Om\Converters\ConverterInterface;
use Talleu\RedisOm\Om\Mapping\Property;
use Talleu\RedisOm\Om\QueryBuilder;
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

    /**
     * @inheritdoc
     */
    abstract public function find($identifier): ?object;

    /**
     * @inheritdoc
     */
    abstract public function getPropertyValue($identifier, string $property): mixed;

    /**
     * @inheritdoc
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);
        $data = $this->redisClient->search($this->prefix, $criteria, $orderBy ?? [], $this->format, $limit);

        $collection = [];
        foreach ($data as $item) {
            $collection[] = $this->converter->revert($item, $this->className);
        }

        return $collection;
    }

    /**
     * @inheritdoc
     */
    public function findByLike(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);
        foreach ($criteria as $property => $value) {
            $criteria[$property.'_text'] = "*$value*";
            unset($criteria[$property]);
        }

        $data = $this->redisClient->search(prefixKey: $this->prefix, search: $criteria, orderBy: $orderBy ?? [], format:  $this->format, numberOfResults: $limit, searchType: Property::INDEX_TEXT);

        $collection = [];
        foreach ($data as $item) {
            $collection[] = $this->converter->revert($item, $this->className);
        }

        return $collection;
    }

    /**
     * @inheritdoc
     */
    public function findLike(string $search, ?int $limit = null): array
    {
        $data = $this->redisClient->searchLike($this->prefix, $search, $this->format, $limit);

        $collection = [];
        foreach ($data as $item) {
            $collection[] = $this->converter->revert($item, $this->className);
        }

        return $collection;
    }

    /**
     * @inheritdoc
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    /**
     * @inheritdoc
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);
        $data = $this->redisClient->search($this->prefix, $criteria, $orderBy ?? [], $this->format, 1);

        if ($data === []) {
            return null;
        }

        return $this->converter->revert($data[0], $this->className);
    }

    /**
     * @inheritdoc
     */
    public function findOneByLike(array $criteria, ?array $orderBy = null): ?object
    {
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);
        foreach ($criteria as $property => $value) {
            $criteria[$property.'_text'] = "*$value*";
            unset($criteria[$property]);
        }

        $data = $this->redisClient->search(prefixKey: $this->prefix, search: $criteria, orderBy: $orderBy ?? [], format:  $this->format, numberOfResults: 1, searchType: Property::INDEX_TEXT);

        if ($data === []) {
            return null;
        }

        return $this->converter->revert($data[0], $this->className);
    }

    /**
     * @inheritdoc
     */
    public function count(array $criteria = []): int
    {
        return $this->redisClient->count($this->prefix, $criteria);
    }

    /**
     * @inheritdoc
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder(
            redisClient:  $this->redisClient,
            converter: $this->converter,
            className: $this->className,
            redisKey: $this->prefix,
            format: $this->format
        );
    }

    /**
     * @inheritdoc
     */
    public function setRedisClient(RedisClientInterface $redisClient): void
    {
        $this->redisClient = $redisClient;
    }

    /**
     * @inheritdoc
     */
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

    protected function convertSpecial(array|string &$criteria): void
    {
        foreach ($criteria as $property => $value) {

            if (is_null($value)) {
                $criteria[$property] = 'null';
                continue;
            }

            if (is_bool($value)) {
                $criteria[$property] = $value ? 'true' : 'false';
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            $criteria[$property] = str_replace([':'], ['\:'], $value);
            $criteria[$property] = str_replace([' '], ['\ '], $criteria[$property]);
        }
    }

    protected function convertDates(array &$criteria): void
    {
        foreach ($criteria as $property => $value) {
            if (!property_exists($this->className, $property)) {
                continue;
            }

            $reflectionProperty = new \ReflectionProperty($this->className, $property);
            /** @var \ReflectionNamedType $reflectionType */
            $reflectionType = $reflectionProperty->getType();
            if (in_array($reflectionType->getName(), AbstractDateTimeConverter::DATETYPES_NAMES)) {

                if (!$value instanceof \DateTimeInterface) {
                    $value = new \DateTime($value);
                }

                $criteria[$property] = strtotime($value->format(AbstractDateTimeConverter::FORMAT));
            }
        }
    }
}
