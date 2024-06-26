<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Client;

use Talleu\RedisOm\Exception\BadPropertyConfigurationException;
use Talleu\RedisOm\Exception\RedisClientResponseException;
use Talleu\RedisOm\Om\Mapping\Property;
use Talleu\RedisOm\Om\RedisFormat;

final class RedisClient implements RedisClientInterface
{
    public function __construct(protected ?\Redis $redis = null)
    {
        $this->redis = $redis ?? new \Redis(array_key_exists('REDIS_HOST', $_SERVER) ? ['host' => $_SERVER['REDIS_HOST']] : null);
    }

    /**
     * @inheritdoc
     */
    public function createPersistentConnection(?string $host = null, ?int $port = null, ?int $timeout = 0): void
    {
        $this->redis->pconnect(
            $host ?? $this->redis->getHost(),
            $port ?? $this->redis->getPort(),
            $timeout ?? $this->redis->getTimeout(),
        );
    }

    /**
     * @inheritdoc
     */
    public function hMSet(string $key, array $data): void
    {
        if (!$this->redis->hMSet(RedisClient::convertPrefix($key), $data)) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function hget(string $key, string $property): string
    {
        $result = $this->redis->hget(RedisClient::convertPrefix($key), $property);

        if (!$result) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hGetAll(string $key): array
    {
        $result = $this->redis->hGetAll(RedisClient::convertPrefix($key));

        if ($result === false) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function del(string $key): void
    {
        if (!$this->redis->del(RedisClient::convertPrefix($key))) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonGet(string $key): ?string
    {
        $result = $this->redis->rawCommand(RedisCommands::JSON_GET->value, static::convertPrefix($key));

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
        $result = $this->redis->rawCommand(RedisCommands::JSON_GET->value, static::convertPrefix($key), "$.$property");

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
        if (!$this->redis->rawCommand(RedisCommands::JSON_SET->value, static::convertPrefix($key), $path, $value)) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
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
                $arguments[] = static::convertPrefix($param[$i]);
                $arguments[] = $param[$i + 1] ?? '$';
                $arguments[] = $param[$i + 2] ?? '{}';
            }
        }

        if (!call_user_func_array([$this->redis, 'rawCommand'], $arguments)) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonDel(string $key, ?string $path = '$'): void
    {
        if (!$this->redis->rawCommand(RedisCommands::JSON_DELETE->value, static::convertPrefix($key), $path)) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function createIndex(string $prefixKey, ?string $format = 'HASH', ?array $properties = []): void
    {
        $prefixKey = self::convertPrefix($prefixKey);

        $arguments = [
            RedisCommands::CREATE_INDEX->value,
            $prefixKey,
            'ON',
            $format,
        ];

        if ($format === RedisFormat::HASH->value) {
            $arguments[] = 'PREFIX';
            $arguments[] = '1';
            $arguments[] = "$prefixKey:";
        }

        $arguments[] = 'SCHEMA';

        foreach ($properties as $name => $as) {
            $arguments[] = ($format === RedisFormat::JSON->value ? '$.' : '') . $name;
            $arguments[] = 'AS';
            $arguments[] = $as;
            $arguments[] = Property::TAG_TYPE;
            $arguments[] = 'SORTABLE';

            $arguments[] = ($format === RedisFormat::JSON->value ? '$.' : '') . $name;
            $arguments[] = 'AS';
            $arguments[] = $as.'_text';
            $arguments[] = Property::TEXT_TYPE;
            $arguments[] = 'SORTABLE';
        }

        if (end($arguments) === 'SCHEMA') {
            throw new BadPropertyConfigurationException(sprintf('Your class %s does not have any typed property', $prefixKey));
        }

        if (!call_user_func_array([$this->redis, 'rawCommand'], $arguments)) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
        }
    }

    /**
     * @inheritdoc
     */
    public function dropIndex(string $prefixKey): bool
    {
        try {
            $key = self::convertPrefix($prefixKey);
            $this->redis->rawCommand(RedisCommands::DROP_INDEX->value, $key);
        } catch (\RedisException) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function count(string $prefixKey, array $criterias = []): int
    {
        $arguments = [RedisCommands::SEARCH->value, static::convertPrefix($prefixKey)];

        foreach ($criterias as $property => $value) {
            $arguments[] = "@$property:$value";
        }

        $rawResult = call_user_func_array([$this->redis, 'rawCommand'], $arguments);

        if (!$rawResult) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
        }

        return (int) $rawResult[0];
    }

    /**
     * @inheritdoc
     */
    public function scanKeys(string $prefixKey): array
    {
        $keys = [];
        $iterator = null;
        while ($iterator !== 0) {
            $scans = $this->redis->scan($iterator, sprintf('%s*', static::convertPrefix($prefixKey)));
            foreach ($scans as $scan) {
                $keys[] = $scan;
            }
        }

        return $keys;
    }

    /**
     * @inheritdoc
     */
    public function flushAll(): void
    {
        if (!$this->redis->flushAll()) {
            $this->handleError(__METHOD__, $this->redis->getLastError());
        }
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
    public function search(string $prefixKey, array $search, array $orderBy, ?string $format = RedisFormat::HASH->value, ?int $numberOfResults = null, ?string $searchType = Property::TAG_TYPE): array
    {
        $arguments = [RedisCommands::SEARCH->value, self::convertPrefix($prefixKey)];

        if ($search === []) {
            $arguments[] = '*';
        } else {
            $criteria = '';
            foreach ($search as $property => $value) {
                if ($searchType === Property::TAG_TYPE) {
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

        try {
            $result = call_user_func_array([$this->redis, 'rawCommand'], $arguments);
        } catch (\RedisException $e) {
            $this->handleError(RedisCommands::SEARCH->value, $e->getMessage(), $e);
        }

        if ($result === false) {
            $this->handleError(RedisCommands::SEARCH->value, $this->redis->getLastError());
        }

        if ($result[0] === 0) {
            return [];
        }

        return $this->extractRedisData($result, $format, $numberOfResults);
    }

    /**
     * @inheritdoc
     */
    public function searchLike(string $prefixKey, string $search, ?string $format = RedisFormat::HASH->value, ?int $numberOfResults = null): array
    {
        $arguments = [RedisCommands::SEARCH->value, static::convertPrefix($prefixKey)];

        $arguments[] = $search;

        try {
            $result = call_user_func_array([$this->redis, 'rawCommand'], $arguments);
        } catch (\RedisException $e) {
            $this->handleError(RedisCommands::SEARCH->value, $e->getMessage(), $e);
        }

        if ($result === false) {
            $this->handleError(RedisCommands::SEARCH->value, $this->redis->getLastError());
        }

        if ($result[0] === 0) {
            return [];
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
