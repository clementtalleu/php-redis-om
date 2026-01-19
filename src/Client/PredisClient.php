<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Client;

use Predis\Client as Predis;
use Predis\Connection\StreamConnection;
use Talleu\RedisOm\Client\Helper\Converter;
use Talleu\RedisOm\Command\PropertyToIndex;
use Talleu\RedisOm\Exception\BadPropertyConfigurationException;
use Talleu\RedisOm\Exception\RedisClientResponseException;
use Talleu\RedisOm\Om\Mapping\Property;
use Talleu\RedisOm\Om\RedisFormat;

final class PredisClient implements RedisClientInterface
{
    public function __construct(protected ?Predis $redis = null)
    {
        $redisConfig = [];

        if (array_key_exists('REDIS_HOST', $_SERVER)) {
            $redisConfig['host'] = $_SERVER['REDIS_HOST'];
        }

        if (array_key_exists('REDIS_PORT', $_SERVER)) {
            $redisConfig['port'] = $_SERVER['REDIS_PORT'];
        }

        if (array_key_exists('REDIS_USER', $_SERVER)) {
            $redisConfig['parameters']['username'] = $_SERVER['REDIS_USER'];
        }

        if (array_key_exists('REDIS_PASSWORD', $_SERVER)) {
            $redisConfig['parameters']['password'] = $_SERVER['REDIS_PASSWORD'];
        }

        $this->redis = $redis ?? new Predis($redisConfig !== [] ? $redisConfig : null);
    }

    public function createPersistentConnection(?string $host = null, ?int $port = null, ?int $timeout = 0): void
    {
        /** @var StreamConnection $connection */
        $connection =  $this->redis->getConnection();
        $parameters = $connection->getParameters()->toArray();

        $this->redis = new Predis([
            'scheme' => 'tcp',
            'host' => $host ?? $parameters['host'],
            'port' => $port ?? $parameters['port'],
            'persistent' => true,
            'timeout' => $timeout,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function hMSet(string $key, array $data): void
    {
        if (!$this->redis->hmset(Converter::prefix($key), $data)) {
            $this->handleError(__METHOD__, $this->getLastError());
        }
    }

    private function getLastError()
    {
        try {
            $this->redis->executeRaw(['INVALID_COMMAND']);
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }


    /**
     * @inheritdoc
     */
    public function hGetAll(string $key): array
    {
        return $this->redis->hgetall(Converter::prefix($key));
    }

    /**
     * @inheritdoc
     */
    public function hget(string $key, string $property): string
    {
        $result = $this->redis->hget(Converter::prefix($key), $property);

        if (!$result) {
            $this->handleError(__METHOD__, $this->getLastError());
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function del(string $key): void
    {
        if (!$this->redis->del(Converter::prefix($key))) {
            $this->handleError(__METHOD__, $this->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonGet(string $key): ?string
    {
        $result = $this->redis->executeRaw([RedisCommands::JSON_GET->value, Converter::prefix($key)]);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function jsonGetProperty(string $key, string $property): ?string
    {
        $result = $this->redis->executeRaw([RedisCommands::JSON_GET->value, Converter::prefix($key), "$.$property"]);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function jsonSet(string $key, ?string $path = '$', ?string $value = '{}'): void
    {
        if (!$this->redis->executeRaw([RedisCommands::JSON_SET->value, Converter::prefix($key), $path, $value])) {
            $this->handleError(__METHOD__, $this->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonMSet(...$params): void
    {
        $arguments = [RedisCommands::JSON_MSET->value];
        foreach ($params as $param) {
            if (count($param) % 3 !== 0) {
                throw new \InvalidArgumentException("Should provide 3 parameters for each key, path and value");
            }

            for ($i = 0; $i < count($param); $i += 3) {
                $arguments[] = Converter::prefix($param[$i]);
                $arguments[] = $param[$i + 1] ?? '$';
                $arguments[] = $param[$i + 2] ?? '{}';
            }
        }

        if (!call_user_func_array([$this->redis, 'executeRaw'], [$arguments])) {
            $this->handleError(__METHOD__, $this->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonDel(string $key, ?string $path = '$'): void
    {
        if (!$this->redis->executeRaw([RedisCommands::JSON_DELETE->value, Converter::prefix($key), $path])) {
            $this->handleError(__METHOD__, $this->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function createIndex(string $prefixKey, ?string $format = RedisFormat::HASH->value, ?array $properties = []): void
    {
        if ($properties === []) {
            return;
        }

        $prefixKey = self::convertPrefix($prefixKey);

        $arguments = [
            RedisCommands::CREATE_INDEX->value,
            $prefixKey,
            'ON',
            $format,
        ];

        $arguments[] = 'PREFIX';
        $arguments[] = '1';
        $arguments[] = "$prefixKey:";

        $arguments[] = 'SCHEMA';

        /** @var PropertyToIndex $propertyToIndex */
        foreach ($properties as $propertyToIndex) {
            if (str_contains($propertyToIndex->indexName, '#timestamp')) {
                $arguments[] = $propertyToIndex->indexName;
                $arguments[] = 'AS';
                $arguments[] = $propertyToIndex->indexName;
                $arguments[] = $propertyToIndex->indexType;
                $arguments[] = 'SORTABLE';
                continue;
            }
            $arguments[] = $propertyToIndex->name;
            $arguments[] = 'AS';
            $arguments[] = $propertyToIndex->indexName;
            $arguments[] = $propertyToIndex->indexType;
            $arguments[] = 'SORTABLE';
        }

        if (end($arguments) === 'SCHEMA') {
            throw new BadPropertyConfigurationException(sprintf('Your class %s does not have any typed property', $prefixKey));
        }

        if (!call_user_func_array([$this->redis, 'executeRaw'], [$arguments])) {
            $this->handleError(__METHOD__, $this->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function dropIndex(string $prefixKey): bool
    {
        try {
            $key = self::convertPrefix($prefixKey);
            $this->redis->executeRaw([RedisCommands::DROP_INDEX->value, $key]);
        } catch (\RedisException) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function count(string $prefixKey, array $criterias = [], ?string $searchType = Property::INDEX_TAG): int
    {
        $arguments = [RedisCommands::SEARCH->value, Converter::prefix($prefixKey)];

        if ($criterias === []) {
            $arguments[] = '*';
        } else {
            foreach ($criterias as $property => $value) {
                if ($searchType === Property::INDEX_TAG) {
                    $arguments[] = sprintf('@%s:{%s}', $property, $value);
                } else {
                    $arguments[] = sprintf('@%s:%s', $property, $value);
                }
            }
        }

        $rawResult = call_user_func_array([$this->redis, 'executeRaw'], [$arguments]);

        if (!$rawResult) {
            $this->handleError(__METHOD__, $this->getLastError());
        }

        return (int)$rawResult[0];
    }

    /**
     * @inheritdoc
     */
    public function scanKeys(string $prefixKey): array
    {
        $keys = [];
        $iterator = 0;
        do {
            $scans = $this->redis->scan($iterator, [sprintf('%s*', Converter::prefix($prefixKey))]);
            if (!empty($scans)) {
                foreach ($scans as $scan) {
                    $keys[] = $scan;
                }
            }
            /** @phpstan-ignore-next-line */
        } while ($iterator !== 0);

        return $keys;
    }

    /**
     * @inheritdoc
     */
    public function flushAll(): void
    {
        if (!$this->redis->flushall()) {
            $this->handleError(__METHOD__, $this->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function expire(string $key, int $ttl): void
    {
        if (!$this->redis->expire(Converter::prefix($key), $ttl)) {
            $this->handleError(__METHOD__, $this->getLastError());
        }
    }

    public function expireTime(string $key): int
    {
        $timestamp = $this->redis->expiretime(Converter::prefix($key));
        if (!$timestamp) {
            $this->handleError(__METHOD__, $this->getLastError());
        }

        return $timestamp;
    }

    /**
     * @inheritdoc
     */
    public function keys(string $pattern): array
    {
        return $this->redis->keys($pattern);
    }

    /**
     * @inheritdoc
     */
    public function search(string $prefixKey, array $search, array $orderBy, ?string $format = RedisFormat::HASH->value, ?int $numberOfResults = null, int $offset = 0, ?string $searchType = Property::INDEX_TAG): array
    {
        $arguments = [RedisCommands::SEARCH->value, self::convertPrefix($prefixKey)];

        if ($search === []) {
            $arguments[] = '*';
        } else {
            $criteria = '';
            foreach ($search as $property => $value) {
                if ($searchType === Property::INDEX_TAG) {
                    $criteria .= sprintf('@%s:{%s}', $property, $value);
                } else {
                    $criteria .= sprintf('@%s:%s', $property, $value);
                }
            }

            $arguments[] = $criteria;
        }

        foreach ($orderBy as $property => $direction) {
            $arguments[] = 'SORTBY';
            $arguments[] = $property;
            $arguments[] = $direction;
        }

        if ($numberOfResults !== null) {
            $arguments[] = 'LIMIT';
            $arguments[] = $offset;
            $arguments[] = $numberOfResults;
        }

        try {
            $result = call_user_func_array([$this->redis, 'executeRaw'], [$arguments]);
        } catch (\RedisException $e) {
            $this->handleError(RedisCommands::SEARCH->value, $e->getMessage(), $e);
        }

        if ($result === false) {
            $this->handleError(RedisCommands::SEARCH->value, $this->getLastError());
        }

        if ($result[0] === 0) {
            return [];
        }

        return $this->extractRedisData((array)$result, $format, $numberOfResults);
    }

    /**
     * @inheritdoc
     */
    public function customSearch(string $prefixKey, string $query, string $format): array
    {
        $arguments = [RedisCommands::SEARCH->value, self::convertPrefix($prefixKey), $query];

        try {
            $result = call_user_func_array([$this->redis, 'executeRaw'], [$arguments]);
        } catch (\RedisException $e) {
            $this->handleError(RedisCommands::SEARCH->value, $e->getMessage(), $e);
        }

        if ($result === false) {
            $this->handleError(RedisCommands::SEARCH->value, $this->getLastError());
        }

        if ($result[0] === 0) {
            return [];
        }

        return $this->extractRedisData($result, $format);
    }

    /**
     * @inheritdoc
     */
    public function searchLike(string $prefixKey, string $search, ?string $format = RedisFormat::HASH->value, ?int $numberOfResults = null): array
    {
        $arguments = [RedisCommands::SEARCH->value, Converter::prefix($prefixKey)];

        $arguments[] = $search;

        try {
            $result = call_user_func_array([$this->redis, 'executeRaw'], [$arguments]);
        } catch (\RedisException $e) {
            $this->handleError(RedisCommands::SEARCH->value, $e->getMessage(), $e);
        }

        if ($result === false) {
            $this->handleError(RedisCommands::SEARCH->value, $this->getLastError());
        }

        if ($result[0] === 0) {
            return [];
        }

        if (!is_array($result)) {
            $this->handleError(RedisCommands::SEARCH->value, 'Unexpected result type from Redis: ' . gettype($result));
        }

        return $this->extractRedisData($result, $format, $numberOfResults);
    }

    public static function convertPrefix(string $key): string
    {
        return str_replace('\\', '_', $key);
    }

    private function handleError(string $command, ?string $errorMessage = 'Unknown error', ?\Throwable $previous = null): never
    {
        throw new RedisClientResponseException(
            sprintf('something was wrong when executing %s command, reason: %s', $command, $errorMessage),
            $previous?->getCode() ?? 0,
            $previous
        );
    }

    private function extractRedisData(array $result, string $format, ?int $numberOfResults = null): array
    {
        $entities = [];
        foreach ($result as $key => $redisData) {
            if ($key > 0 && $key % 2 == 0) {

                if ($format === RedisFormat::JSON->value) {
                    foreach ($redisData as $data) {
                        if (!str_starts_with($data, '{')) {
                            continue;
                        }
                        $entities[] = json_decode($data, true);
                        break;
                    }

                    continue;
                } else {
                    $data = [];
                    for ($i = 0; $i < count($redisData); $i += 2) {
                        $property = $redisData[$i];
                        $value = $redisData[$i + 1];
                        $data[$property] = $value;
                    }
                }

                $entities[] = $data;

                if (count($entities) === $numberOfResults) {
                    return $entities;
                }
            }
        }

        return $entities;
    }
}
