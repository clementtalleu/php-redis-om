<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Client;

use Talleu\RedisOm\Om\Mapping\Property;
use Talleu\RedisOm\Om\RedisFormat;

interface RedisClientInterface
{
    /**
     * Create a persistent connection to the Redis server.
     */
    public function createPersistentConnection(?string $host = null, ?int $port = null, ?int $timeout = 0): void;

    /**
     * Persist multiple hash objects to the Redis datastore.
     */
    public function hMSet(string $key, array $data): void;

    /**
     * Get all properties of a hash object from the Redis datastore by given key.
     */
    public function hGetAll(string $key): array;

    /**
     * Get a specific property of a hash object from the Redis datastore by given key.
     */
    public function hget(string $key, string $property): string;

    /**
     * Remove an entry from the Redis datastore by given key.
     */
    public function del(string $key): void;

    /**
     * Get a JSON object from the Redis datastore by given key.
     */
    public function jsonGet(string $key): ?string;

    /**
     * Get a specific property of a JSON object from the Redis datastore by given key.
     */
    public function jsonGetProperty(string $key, string $property): ?string;

    /**
     * Set a JSON object to the Redis datastore by given key.
     */
    public function jsonSet(string $key, ?string $path = '$', ?string $value = '{}'): void;

    /**
     * Set multiple JSON objects to the Redis datastore.
     */
    public function jsonMSet(...$params): void;

    /**
     * Remove a JSON object from the Redis datastore by given key.
     * Should provide a set of parameters systematically composed of a key / path / value for each JSON object to be persisted
     */
    public function jsonDel(string $key, ?string $path = '$'): void;

    /**
     * Create index for objects by properties.
     */
    public function createIndex(string $prefixKey, ?string $format = 'HASH', ?array $properties = []): void;

    /**
     * Remove all index for given prefix key.
     */
    public function dropIndex(string $prefixKey): bool;

    /**
     * Count all objects by given prefix key and criterias.
     */
    public function count(string $prefixKey, array $criterias = []): int;

    /**
     * Search objects by given prefix key and criterias.
     */
    public function search(string $prefixKey, array $search, array $orderBy, ?string $format = RedisFormat::HASH->value, ?int $numberOfResults = null, ?string $searchType = Property::TAG_TYPE): array;

    /**
     * Search objects by given prefix key and full text.
     */
    public function searchLike(string $prefixKey, string $search, ?string $format = RedisFormat::HASH->value, ?int $numberOfResults = null): array;

    /**
     * Retrieve all keys by given prefix key, use * for all keys (do not use in production).
     */
    public function scanKeys(string $prefixKey): array;

    /**
     * Remove all keys from the Redis datastore. Do not use in production.
     */
    public function flushAll(): void;
}
