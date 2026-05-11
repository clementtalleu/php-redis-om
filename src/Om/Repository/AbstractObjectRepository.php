<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository;

use Talleu\RedisOm\Client\Helper\Converter;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Exception\BadIdentifierConfigurationException;
use Talleu\RedisOm\Exception\BulkOperationException;
use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;
use Talleu\RedisOm\Om\Converters\ConverterInterface;
use Talleu\RedisOm\Om\Mapping\Id;
use Talleu\RedisOm\Om\Mapping\Property;
use Talleu\RedisOm\Om\Metadata\ClassMetadata;
use Talleu\RedisOm\Om\Metadata\MetadataFactory;
use Talleu\RedisOm\Om\Paginator;
use Talleu\RedisOm\Om\QueryBuilder;
use Talleu\RedisOm\Om\RedisFormat;

abstract class AbstractObjectRepository implements RepositoryInterface
{
    public ?string $prefix = null;
    public ?string $className = null;
    protected ?RedisClientInterface $redisClient = null;
    protected ?ConverterInterface $converter = null;
    private ?ClassMetadata $classMetadata = null;
    private const DEFAULT_SEARCH_LIMIT = 10000;

    public function __construct(public ?string $format = null)
    {
    }

    protected function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata ??= (new MetadataFactory())->createClassMetadata($this->className);
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
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = 0, array $additionalRangeFilters = []): array
    {
        $limit = $this->defineLimit($limit);
        $rangeFilters = array_merge($this->extractRangeFilters($criteria), $additionalRangeFilters);
        $this->convertObjects($criteria);
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);
        $data = $this->redisClient->search(
            $this->prefix,
            $criteria,
            $this->rewriteOrderBy($orderBy),
            $this->format,
            $limit,
            offset: $offset,
            rangeFilters: $rangeFilters,
        );

        $collection = [];
        foreach ($data as $item) {
            $collection[] = $this->converter->revert($item, $this->className);
        }

        return $collection;
    }

    /**
     * @inheritdoc
     */
    public function findByLike(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = 0, array $additionalRangeFilters = []): array
    {
        return $this->findByPattern($criteria, '*%s*', $orderBy, $limit, $offset, $additionalRangeFilters);
    }

    /**
     * @inheritdoc
     */
    public function findByStartWith(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = 0, array $additionalRangeFilters = []): array
    {
        return $this->findByPattern($criteria, '%s*', $orderBy, $limit, $offset, $additionalRangeFilters);
    }

    /**
     * @inheritdoc
     */
    public function findByEndWith(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = 0, array $additionalRangeFilters = []): array
    {
        return $this->findByPattern($criteria, '*%s', $orderBy, $limit, $offset, $additionalRangeFilters);
    }

    private function findByPattern(array $criteria, string $pattern, ?array $orderBy, ?int $limit, ?int $offset, array $additionalRangeFilters = []): array
    {
        $limit = $this->defineLimit($limit);
        $this->convertObjects($criteria);
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);

        $transformedCriteria = [];
        foreach ($criteria as $property => $value) {
            $transformedCriteria["{$property}_text"] = sprintf($pattern, $value);
        }

        $data = $this->redisClient->search(
            prefixKey: $this->prefix,
            search: $transformedCriteria,
            orderBy: $this->rewriteOrderBy($orderBy),
            format: $this->format,
            numberOfResults: $limit,
            offset: $offset,
            searchType: Property::INDEX_TEXT,
            rangeFilters: $additionalRangeFilters
        );

        return array_map(
            fn ($item) => $this->converter->revert($item, $this->className),
            $data
        );
    }

    private function defineLimit(?int $limit = null)
    {
        if ($limit === null) {
            $limit = self::DEFAULT_SEARCH_LIMIT;
        }

        return $limit;
    }

    /**
     * @inheritdoc
     */
    public function findLike(string $search, ?int $limit = null): array
    {
        $limit = $this->defineLimit($limit);

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
    public function findByGeoRadius(string $geoField, float $longitude, float $latitude, float $radius, string $unit = 'km', ?int $limit = null): array
    {
        $limit = $this->defineLimit($limit);
        $geoQuery = sprintf('@%s:[%s %s %s %s]', $geoField, $longitude, $latitude, $radius, $unit);

        $data = $this->redisClient->search(
            $this->prefix,
            [],
            [],
            $this->format,
            $limit,
            rangeFilters: ['_geo' => $geoQuery],
        );

        $collection = [];
        foreach ($data as $item) {
            $collection[] = $this->converter->revert($item, $this->className);
        }

        return $collection;
    }

    /**
     * @inheritdoc
     */
    public function paginate(array $criteria = [], int $page = 1, int $itemsPerPage = 20, ?array $orderBy = null): Paginator
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $itemsPerPage;

        $rangeFilters = $this->extractRangeFilters($criteria);
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);

        $totalItems = $this->redisClient->count($this->prefix, $criteria);
        $data = $this->redisClient->search(
            $this->prefix,
            $criteria,
            $this->rewriteOrderBy($orderBy),
            $this->format,
            $itemsPerPage,
            offset: $offset,
            rangeFilters: $rangeFilters,
        );

        $items = [];
        foreach ($data as $item) {
            $items[] = $this->converter->revert($item, $this->className);
        }

        return new Paginator($items, $totalItems, $page, $itemsPerPage);
    }

    /**
     * @inheritdoc
     */
    public function findAll(): iterable
    {
        return $this->stream(batchSize: self::DEFAULT_SEARCH_LIMIT);
    }

    /**
     * @inheritdoc
     */
    public function stream(
        array $criteria = [],
        ?array $orderBy = null,
        int $batchSize = 100,
    ): \Generator {
        $offset = 0;
        do {
            $batch = $this->findBy($criteria, $orderBy, $batchSize, $offset);
            foreach ($batch as $object) {
                yield $object;
            }
            $offset += $batchSize;
        } while (count($batch) === $batchSize);
    }

    /**
     * @inheritdoc
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        $rangeFilters = $this->extractRangeFilters($criteria);
        $this->convertObjects($criteria);
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);
        $data = $this->redisClient->search($this->prefix, $criteria, $this->rewriteOrderBy($orderBy), $this->format, 1, rangeFilters: $rangeFilters);

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
        $this->convertObjects($criteria);
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);
        foreach ($criteria as $property => $value) {
            $criteria[$property . '_text'] = "*$value*";
            unset($criteria[$property]);
        }

        $data = $this->redisClient->search(prefixKey: $this->prefix, search: $criteria, orderBy: $this->rewriteOrderBy($orderBy), format: $this->format, numberOfResults: 1, searchType: Property::INDEX_TEXT);

        if ($data === []) {
            return null;
        }

        return $this->converter->revert($data[0], $this->className);
    }

    /**
     * @inheritdoc
     */
    public function count(array $criteria = [], array $additionalRangeFilters = []): int
    {
        $rangeFilters = array_merge($this->extractRangeFilters($criteria), $additionalRangeFilters);
        $this->convertObjects($criteria);
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);

        return $this->redisClient->count($this->prefix, $criteria, rangeFilters: $rangeFilters);
    }

    /**
     * @inheritdoc
     */
    public function countByLike(array $criteria = []): int
    {
        $this->convertObjects($criteria);
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);
        foreach ($criteria as $property => $value) {
            $criteria[$property . '_text'] = "*$value*";
            unset($criteria[$property]);
        }

        return $this->redisClient->count($this->prefix, $criteria, Property::INDEX_TEXT);
    }

    /**
     * @inheritdoc
     */
    public function bulkDelete(array $criteria = []): int
    {
        $metadata = $this->getClassMetadata();
        $uniqueConstraints = $metadata->uniqueConstraints;
        $batchSize = 100;
        $deleted = 0;
        $batchCount = 0;

        $this->convertObjects($criteria);
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);

        do {
            if ($uniqueConstraints === []) {
                // No unique keys to clean up: fetch only key names
                $keys = $this->redisClient->searchKeys($this->prefix, $criteria, $batchSize, 0);
                $batchCount = count($keys);
                if ($batchCount > 0) {
                    $this->redisClient->delMultiple($keys);
                    $deleted += $batchCount;
                }
            } else {
                // Fetch key names + unique-field values to delete constraint keys
                $uniqueFields = $metadata->getUniqueFields();
                $searchFields = $this->format === RedisFormat::JSON->value
                    ? array_map(fn ($f) => '$.' . $f, $uniqueFields)
                    : $uniqueFields;

                $entries = $this->redisClient->searchKeysWithFields($this->prefix, $criteria, $searchFields, $batchSize, 0);
                $batchCount = count($entries);
                if ($batchCount > 0) {
                    $isJson = $this->format === RedisFormat::JSON->value;
                    $keysToDelete = [];
                    foreach ($entries as $entry) {
                        $keysToDelete[] = $entry['key'];
                        $fieldValues = $this->normalizeFieldNames($entry['fields']);
                        foreach ($uniqueConstraints as $fields) {
                            $sortedFields = $fields;
                            sort($sortedFields);
                            $values = array_map(function (string $f) use ($fieldValues, $isJson): string {
                                $raw = (string) ($fieldValues[$f] ?? '');
                                // FT.SEARCH RETURN wraps JSON path results in an array: ["actual_value"]
                                if ($isJson && str_starts_with($raw, '[')) {
                                    $decoded = json_decode($raw, true);
                                    return (string) (is_array($decoded) ? ($decoded[0] ?? '') : $decoded);
                                }
                                return $raw;
                            }, $sortedFields);
                            $keysToDelete[] = sprintf(
                                'unique:%s:%s:%s',
                                $this->className,
                                implode(',', $sortedFields),
                                implode(':', $values),
                            );
                        }
                    }
                    $this->redisClient->delMultiple($keysToDelete);
                    $deleted += $batchCount;
                }
            }
        } while ($batchCount === $batchSize);

        return $deleted;
    }

    /**
     * @inheritdoc
     */
    public function bulkUpdate(array $criteria, array $changes): int
    {
        $metadata = $this->getClassMetadata();
        $conflictingFields = array_values(array_intersect(array_keys($changes), $metadata->getUniqueFields()));
        if ($conflictingFields !== []) {
            throw BulkOperationException::uniqueFieldsCannotBeBulkUpdated($this->className, $conflictingFields);
        }

        $this->convertObjects($criteria);
        $this->convertDates($criteria);
        $this->convertSpecial($criteria);

        $isJson = $this->format === RedisFormat::JSON->value;
        $batchSize = 100;
        $offset = 0;
        $updated = 0;

        do {
            $keys = $this->redisClient->searchKeys($this->prefix, $criteria, $batchSize, $offset);
            $batchCount = count($keys);

            if ($batchCount > 0) {
                if ($isJson) {
                    foreach ($keys as $key) {
                        foreach ($changes as $field => $value) {
                            $this->redisClient->jsonSetProperty($key, $field, (string) json_encode($value));
                        }
                    }
                } else {
                    $convertedChanges = $this->convertChangesForHash($changes);
                    foreach ($keys as $key) {
                        $this->redisClient->hMSet($key, $convertedChanges);
                    }
                }
                $updated += $batchCount;
            }

            $offset += $batchSize;
        } while ($batchCount === $batchSize);

        return $updated;
    }

    /**
     * Strips `$.` JSON path prefix from field names returned by FT.SEARCH RETURN.
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function normalizeFieldNames(array $fields): array
    {
        $normalized = [];
        foreach ($fields as $fieldName => $value) {
            $normalized[str_starts_with($fieldName, '$.') ? substr($fieldName, 2) : $fieldName] = $value;
        }

        return $normalized;
    }

    private function convertChangesForHash(array $changes): array
    {
        $converted = [];
        foreach ($changes as $field => $value) {
            $converted[$field] = match (true) {
                is_null($value) => 'null',
                is_bool($value) => $value ? 'true' : 'false',
                default => (string) $value,
            };
        }

        return $converted;
    }

    /**
     * @inheritdoc
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder(
            redisClient: $this->redisClient,
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

    /**
     * Extract range filters from criteria.
     * Range filters use MongoDB-style operators: $gte, $gt, $lte, $lt
     * Example: ['age' => ['$gte' => 18, '$lte' => 65]] → @age:[18 65]
     *
     * @return array<string, string> Numeric range query parts keyed by property
     */
    /**
     * Extract range filters from criteria.
     * Range filters use MongoDB-style operators: $gte, $gt, $lte, $lt
     * Example: ['age' => ['$gte' => 18, '$lte' => 65]] → @age_numeric:[18 65]
     *
     * Requires a NUMERIC index on the property (automatic for HASH int/float,
     * use #[Property(index: ['field' => 'NUMERIC'])] for JSON).
     *
     * @return array<string, string> Numeric range query parts keyed by property
     */
    protected function extractRangeFilters(array &$criteria): array
    {
        $rangeFilters = [];

        foreach ($criteria as $property => $value) {
            if (!is_array($value)) {
                continue;
            }

            $rangeKeys = array_intersect(array_keys($value), ['$gte', '$gt', '$lte', '$lt']);
            if (empty($rangeKeys)) {
                continue;
            }

            $min = '-inf';
            $max = '+inf';

            if (isset($value['$gte'])) {
                $min = (string) $value['$gte'];
            } elseif (isset($value['$gt'])) {
                $min = '(' . $value['$gt'];
            }

            if (isset($value['$lte'])) {
                $max = (string) $value['$lte'];
            } elseif (isset($value['$lt'])) {
                $max = '(' . $value['$lt'];
            }

            // Use _numeric alias (auto-created for HASH int/float, must be explicit for JSON)
            $numericAlias = $property . '_numeric';
            $rangeFilters[$property] = sprintf('@%s:[%s %s]', $numericAlias, $min, $max);
            unset($criteria[$property]);
        }

        return $rangeFilters;
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

    /**
     * Resolves object values in criteria to their identifier.
     *
     * Given `['author' => $userObject]`, rewrites the entry to
     * `['author_id' => $userObject->id]` so the existing RediSearch
     * index (created as `{property}_{subProperty}`) matches.
     *
     * The identifier property is the one marked with #[Id] on the value's class.
     */
    protected function convertObjects(array &$criteria): void
    {
        foreach ($criteria as $property => $value) {
            if (!is_object($value) || $value instanceof \DateTimeInterface) {
                continue;
            }

            $idPropertyName = $this->resolveIdPropertyName($value);
            $idValue = $this->extractIdValue($value, $idPropertyName);

            if ($idValue === null) {
                throw new BadIdentifierConfigurationException(sprintf(
                    'Cannot use object of class %s as criterion "%s": its identifier property "%s" is null.',
                    get_class($value),
                    $property,
                    $idPropertyName,
                ));
            }

            unset($criteria[$property]);
            $criteria[$property . '_' . $idPropertyName] = $idValue;
        }
    }

    private function resolveIdPropertyName(object $object): string
    {
        $reflectionClass = new \ReflectionClass($object);
        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->getAttributes(Id::class) !== []) {
                return $property->getName();
            }
        }

        throw new BadIdentifierConfigurationException(sprintf(
            'Cannot use object of class %s as a criterion: no property is marked with #[Id].',
            $reflectionClass->getName(),
        ));
    }

    private function extractIdValue(object $object, string $idPropertyName): mixed
    {
        $reflectionProperty = new \ReflectionProperty($object, $idPropertyName);

        return $reflectionProperty->getValue($object);
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

                $criteria[$property] = $value->getTimestamp();
            }
        }
    }

    /**
     * Rewrite sort keys so int/float properties sort numerically instead of lexicographically.
     *
     * RediSearch TAG fields sort as strings ("10.2" < "100.0" < "9.5"), so sorting floats
     * or ints via the default alias yields wrong order. For these types the schema exposes
     * a parallel NUMERIC alias `{property}_numeric` (SORTABLE); this method swaps the key
     * so SORTBY targets that alias instead.
     *
     * @param array<string, string>|null $orderBy
     * @return array<string, string>
     */
    protected function rewriteOrderBy(?array $orderBy): array
    {
        if (empty($orderBy) || $this->className === null) {
            return $orderBy ?? [];
        }

        $rewritten = [];
        foreach ($orderBy as $property => $direction) {
            $rewritten[$this->resolveSortField((string) $property)] = $direction;
        }

        return $rewritten;
    }

    private function resolveSortField(string $property): string
    {
        // Auto-rewriting to the NUMERIC alias is only safe for HASH: Redis parses string values
        // at indexing time so a NUMERIC index on a string HASH field just works. In JSON format
        // ScalarConverter stores scalars as strings, and RediSearch rejects string values at a
        // NUMERIC JSONPath — so users must opt in via #[Property(index: [... => 'NUMERIC'])].
        if ($this->format !== RedisFormat::HASH->value) {
            return $property;
        }

        if (!property_exists($this->className, $property)) {
            return $property;
        }

        $reflectionType = (new \ReflectionProperty($this->className, $property))->getType();
        if (!$reflectionType instanceof \ReflectionNamedType) {
            return $property;
        }

        $typeName = $reflectionType->getName();
        if ($typeName === 'int' || $typeName === 'float') {
            return $property . '_numeric';
        }

        if (class_exists($typeName) && is_subclass_of($typeName, \BackedEnum::class)) {
            $backingType = (new \ReflectionEnum($typeName))->getBackingType()?->getName();
            if ($backingType === 'int') {
                return $property . '_numeric';
            }
        }

        return $property;
    }
}
