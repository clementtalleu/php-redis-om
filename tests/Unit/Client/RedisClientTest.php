<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Exception\RedisClientResponseException;
use Talleu\RedisOm\Om\Mapping\Property;

final class RedisClientTest extends TestCase
{
    private function makeClient(\Redis $redis): RedisClient
    {
        return new RedisClient($redis);
    }

    private function mockRedis(): \Redis
    {
        $mock = $this->createMock(\Redis::class);
        $mock->method('getLastError')->willReturn('ERR test error');

        return $mock;
    }

    // --- hMSet ---

    public function testHMSetThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('hMSet')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->hMSet('key', ['field' => 'value']);
    }

    public function testHMSetSucceeds(): void
    {
        $mock = $this->mockRedis();
        $mock->expects($this->once())->method('hMSet')->willReturn(true);

        $this->makeClient($mock)->hMSet('key', ['field' => 'value']);
    }

    // --- hget ---

    public function testHgetThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('hget')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->hget('key', 'field');
    }

    public function testHgetReturnsValue(): void
    {
        $mock = $this->mockRedis();
        $mock->method('hget')->willReturn('hello');

        $this->assertSame('hello', $this->makeClient($mock)->hget('key', 'field'));
    }

    // --- hSet ---

    public function testHSetCallsRedisHSet(): void
    {
        $mock = $this->mockRedis();
        $mock->expects($this->once())->method('hSet')->with('key', 'field', 'value');

        $this->makeClient($mock)->hSet('key', 'field', 'value');
    }

    // --- hGetAll ---

    public function testHGetAllThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('hGetAll')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->hGetAll('key');
    }

    public function testHGetAllReturnsArray(): void
    {
        $mock = $this->mockRedis();
        $mock->method('hGetAll')->willReturn(['id' => '1', 'name' => 'test']);

        $this->assertSame(['id' => '1', 'name' => 'test'], $this->makeClient($mock)->hGetAll('key'));
    }

    // --- del ---

    public function testDelThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('del')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->del('key');
    }

    // --- jsonGet ---

    public function testJsonGetReturnsNullWhenKeyNotFound(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->assertNull($this->makeClient($mock)->jsonGet('key'));
    }

    public function testJsonGetReturnsJsonString(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn('{"id":"1"}');

        $this->assertSame('{"id":"1"}', $this->makeClient($mock)->jsonGet('key'));
    }

    // --- jsonGetProperty ---

    public function testJsonGetPropertyReturnsNullWhenKeyNotFound(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->assertNull($this->makeClient($mock)->jsonGetProperty('key', 'field'));
    }

    public function testJsonGetPropertyReturnsValue(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn('["hello"]');

        $this->assertSame('["hello"]', $this->makeClient($mock)->jsonGetProperty('key', 'field'));
    }

    // --- jsonSet ---

    public function testJsonSetThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->jsonSet('key', '$', '{}');
    }

    // --- jsonDel ---

    public function testJsonDelThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->jsonDel('key');
    }

    // --- jsonMSet ---

    public function testJsonMSetThrowsOnInvalidParamCount(): void
    {
        $mock = $this->mockRedis();

        $this->expectException(\InvalidArgumentException::class);
        $this->makeClient($mock)->jsonMSet(['key', '$']); // count=2, not multiple of 3
    }

    public function testJsonMSetThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->jsonMSet(['key', '$', '{}']);
    }

    // --- createIndex ---

    public function testCreateIndexDoesNothingWhenPropertiesEmpty(): void
    {
        $mock = $this->mockRedis();
        $mock->expects($this->never())->method('rawCommand');

        $this->makeClient($mock)->createIndex('TestIndex', 'HASH', []);
    }

    public function testCreateIndexThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $property = (object)['name' => 'name', 'indexName' => 'name', 'indexType' => Property::INDEX_TAG];

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->createIndex('TestIndex', 'HASH', [$property]);
    }

    // --- dropIndex ---

    public function testDropIndexReturnsFalseOnRedisException(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willThrowException(new \RedisException('ERR no such index'));

        $this->assertFalse($this->makeClient($mock)->dropIndex('NonExistentIndex'));
    }

    public function testDropIndexReturnsTrueOnSuccess(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn('OK');

        $this->assertTrue($this->makeClient($mock)->dropIndex('ExistingIndex'));
    }

    // --- count ---

    public function testCountThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->count('TestIndex', ['field' => 'value']);
    }

    public function testCountReturnsZeroWhenNoResults(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([0]);

        $this->assertSame(0, $this->makeClient($mock)->count('TestIndex'));
    }

    public function testCountReturnsCorrectCount(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([2, 'key1', [], 'key2', []]);

        $this->assertSame(2, $this->makeClient($mock)->count('TestIndex'));
    }

    public function testCountWithTagCriteriaBuildsCorrectQuery(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $query = $args[2] ?? '';
            $this->assertStringContainsString('@name:{Alice}', $query);
            return [1, 'key1', []];
        });

        $this->makeClient($mock)->count('TestIndex', ['name' => 'Alice'], Property::INDEX_TAG);
    }

    public function testCountWithNumericSearchType(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $query = $args[2] ?? '';
            $this->assertStringContainsString('@age:30', $query);
            $this->assertStringNotContainsString('{', $query);
            return [1, 'key1', []];
        });

        $this->makeClient($mock)->count('TestIndex', ['age' => '30'], Property::INDEX_NUMERIC);
    }

    public function testCountWithRangeFilters(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $query = $args[2] ?? '';
            $this->assertStringContainsString('@price:[10 20]', $query);
            return [1, 'key1', []];
        });

        $this->makeClient($mock)->count('TestIndex', [], null, ['@price:[10 20]']);
    }

    // --- scanKeys ---

    public function testScanKeysReturnsEmptyWhenScanReturnsFalse(): void
    {
        $mock = $this->mockRedis();
        $mock->method('scan')->willReturn(false);

        $this->assertSame([], $this->makeClient($mock)->scanKeys('TestPrefix'));
    }

    public function testScanKeysReturnsAllFoundKeys(): void
    {
        $mock = $this->mockRedis();
        $mock->method('scan')
            ->willReturnCallback(function (&$iterator, string $pattern): array {
                $iterator = 0;
                return ['TestPrefix:1', 'TestPrefix:2'];
            });

        $result = $this->makeClient($mock)->scanKeys('TestPrefix');

        $this->assertCount(2, $result);
        $this->assertContains('TestPrefix:1', $result);
        $this->assertContains('TestPrefix:2', $result);
    }

    // --- flushAll ---

    public function testFlushAllThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('flushAll')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->flushAll();
    }

    // --- expire ---

    public function testExpireThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('expire')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->expire('key', 3600);
    }

    // --- expireTime ---

    public function testExpireTimeThrowsOnRedisError(): void
    {
        $mock = $this->mockRedis();
        $mock->method('expireTime')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->expireTime('key');
    }

    public function testExpireTimeReturnsTimestamp(): void
    {
        $mock = $this->mockRedis();
        $mock->method('expireTime')->willReturn(1700000000);

        $this->assertSame(1700000000, $this->makeClient($mock)->expireTime('key'));
    }

    // --- hGetAllMultiple ---

    public function testHGetAllMultipleReturnsPipelineResults(): void
    {
        $mock = $this->mockRedis();
        $mock->method('pipeline')->willReturn($mock);
        $mock->method('exec')->willReturn([
            ['id' => '1', 'name' => 'Alice'],
            [],
            ['id' => '3', 'name' => 'Charlie'],
        ]);

        $result = $this->makeClient($mock)->hGetAllMultiple(['Key:1', 'Key:2', 'Key:3']);

        $this->assertCount(2, $result);
        $this->assertSame(['id' => '1', 'name' => 'Alice'], $result['Key:1']);
        $this->assertSame(['id' => '3', 'name' => 'Charlie'], $result['Key:3']);
        $this->assertArrayNotHasKey('Key:2', $result);
    }

    // --- jsonGetMultiple ---

    public function testJsonGetMultipleReturnsPipelineResults(): void
    {
        $mock = $this->mockRedis();
        $mock->method('pipeline')->willReturn($mock);
        $mock->method('exec')->willReturn([
            '{"id":"1"}',
            false,
        ]);

        $result = $this->makeClient($mock)->jsonGetMultiple(['Key:1', 'Key:2']);

        $this->assertSame('{"id":"1"}', $result['Key:1']);
        $this->assertNull($result['Key:2']);
    }

    // --- multi / exec / discard ---

    public function testMultiCallsRedisMulti(): void
    {
        $mock = $this->mockRedis();
        $mock->expects($this->once())->method('multi');

        $this->makeClient($mock)->multi();
    }

    public function testExecCallsRedisExec(): void
    {
        $mock = $this->mockRedis();
        $mock->expects($this->once())->method('exec');

        $this->makeClient($mock)->exec();
    }

    public function testDiscardCallsRedisDiscard(): void
    {
        $mock = $this->mockRedis();
        $mock->expects($this->once())->method('discard');

        $this->makeClient($mock)->discard();
    }

    // --- keys ---

    public function testKeysReturnsMatchingKeys(): void
    {
        $mock = $this->mockRedis();
        $mock->method('keys')->willReturn(['key:1', 'key:2']);

        $this->assertSame(['key:1', 'key:2'], $this->makeClient($mock)->keys('key:*'));
    }

    // --- getIndexInfo ---

    public function testGetIndexInfoReturnsEmptyArrayWhenFalse(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->assertSame([], $this->makeClient($mock)->getIndexInfo('TestIndex'));
    }

    public function testGetIndexInfoReturnsEmptyArrayWhenNoAttributesKey(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(['index_name', 'TestIndex', 'num_docs', '0']);

        $this->assertSame([], $this->makeClient($mock)->getIndexInfo('TestIndex'));
    }

    public function testGetIndexInfoParsesAttributes(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([
            'index_name', 'TestIndex',
            'attributes', [
                ['identifier', 'name', 'attribute', 'name', 'type', 'TAG'],
            ],
        ]);

        $result = $this->makeClient($mock)->getIndexInfo('TestIndex');

        $this->assertCount(1, $result);
        $this->assertSame('name', $result[0]['identifier']);
        $this->assertSame('TAG', $result[0]['type']);
    }

    // --- search ---

    public function testSearchThrowsOnFalseResult(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->search('TestIndex', [], [], 'HASH');
    }

    public function testSearchThrowsOnRedisException(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willThrowException(new \RedisException('ERR index not found'));

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->search('TestIndex', [], [], 'HASH');
    }

    public function testSearchReturnsEmptyWhenCountIsZero(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([0]);

        $result = $this->makeClient($mock)->search('TestIndex', [], [], 'HASH');

        $this->assertSame([], $result);
    }

    public function testSearchExtractsHashData(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([
            1,
            'TestIndex:1',
            ['id', '1', 'name', 'Alice'],
        ]);

        $result = $this->makeClient($mock)->search('TestIndex', [], [], 'HASH');

        $this->assertCount(1, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('Alice', $result[0]['name']);
    }

    public function testSearchExtractsJsonData(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([
            1,
            'TestIndex:1',
            ['$', '{"id":"1","name":"Alice"}'],
        ]);

        $result = $this->makeClient($mock)->search('TestIndex', [], [], 'JSON');

        $this->assertCount(1, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('Alice', $result[0]['name']);
    }

    // --- customSearch ---

    public function testCustomSearchThrowsOnFalseResult(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->customSearch('TestIndex', '*', 'HASH');
    }

    public function testCustomSearchReturnsEmptyWhenCountIsZero(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([0]);

        $this->assertSame([], $this->makeClient($mock)->customSearch('TestIndex', '*', 'HASH'));
    }

    public function testCustomSearchExtractsHashData(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([
            1,
            'TestIndex:1',
            ['id', '1', 'name', 'Bob'],
        ]);

        $result = $this->makeClient($mock)->customSearch('TestIndex', '@name:{Bob}', 'HASH');

        $this->assertCount(1, $result);
        $this->assertSame('Bob', $result[0]['name']);
    }

    // --- searchLike ---

    public function testSearchLikeThrowsOnFalseResult(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->searchLike('TestIndex', 'Alice', 'HASH');
    }

    public function testSearchLikeThrowsOnRedisException(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willThrowException(new \RedisException('ERR index not found'));

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->searchLike('TestIndex', 'Alice', 'HASH');
    }

    public function testSearchLikeReturnsEmptyWhenCountIsZero(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([0]);

        $this->assertSame([], $this->makeClient($mock)->searchLike('TestIndex', 'Alice', 'HASH'));
    }

    public function testSearchLikeExtractsHashData(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([
            1,
            'TestIndex:1',
            ['id', '1', 'name', 'Alice'],
        ]);

        $result = $this->makeClient($mock)->searchLike('TestIndex', 'Alice', 'HASH');

        $this->assertCount(1, $result);
        $this->assertSame('Alice', $result[0]['name']);
    }

    // --- customSearch RedisException ---

    public function testCustomSearchThrowsOnRedisException(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willThrowException(new \RedisException('ERR index not found'));

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->customSearch('TestIndex', '*', 'HASH');
    }

    // --- jsonSetProperty ---

    public function testJsonSetPropertyCallsRawCommand(): void
    {
        $mock = $this->mockRedis();
        $mock->expects($this->once())->method('rawCommand');

        $this->makeClient($mock)->jsonSetProperty('key', 'field', '"value"');
    }

    // --- createIndex #timestamp branch ---

    public function testCreateIndexHandlesTimestampProperty(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $schema = implode(' ', $args);
            $this->assertStringContainsString('created_at#timestamp', $schema);
            $this->assertStringContainsString('SORTABLE', $schema);
            return true;
        });

        $property = (object)[
            'name' => 'created_at#timestamp',
            'indexName' => 'created_at#timestamp',
            'indexType' => 'NUMERIC',
        ];

        $this->makeClient($mock)->createIndex('TestIndex', 'HASH', [$property]);
    }

    // --- count: criteria with dash (UUID-like escape) ---

    public function testCountEscapesDashInCriteriaValue(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $query = $args[2] ?? '';
            $this->assertStringContainsString('\-', $query);
            $this->assertStringNotContainsString('550e8400-e29b', $query);
            return [1, 'key1', []];
        });

        $this->makeClient($mock)->count('TestIndex', ['id' => '550e8400-e29b-41d4-a716-446655440000'], Property::INDEX_TAG);
    }

    // --- search: full branch coverage ---

    public function testSearchWithTagCriteria(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $query = $args[2] ?? '';
            $this->assertStringContainsString('@name:{Alice}', $query);
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', ['name' => 'Alice'], [], 'HASH', null, 0, Property::INDEX_TAG);
    }

    public function testSearchWithNumericCriteria(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $query = $args[2] ?? '';
            $this->assertStringContainsString('@age:30', $query);
            $this->assertStringNotContainsString('{', $query);
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', ['age' => '30'], [], 'HASH', null, 0, Property::INDEX_NUMERIC);
    }

    public function testSearchWithOrderBy(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $flat = implode(' ', $args);
            $this->assertStringContainsString('SORTBY', $flat);
            $this->assertStringContainsString('name', $flat);
            $this->assertStringContainsString('ASC', $flat);
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', [], ['name' => 'ASC'], 'HASH');
    }

    public function testSearchWithLimit(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $flat = implode(' ', $args);
            $this->assertStringContainsString('LIMIT', $flat);
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', [], [], 'HASH', 10, 20);
    }

    public function testSearchWithRangeFilters(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $query = $args[2] ?? '';
            $this->assertStringContainsString('@price:[10 50]', $query);
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', [], [], 'HASH', null, 0, null, ['@price:[10 50]']);
    }

    public function testSearchEscapesDashInCriteria(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturnCallback(function (...$args) {
            $query = $args[2] ?? '';
            $this->assertStringContainsString('\-', $query);
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', ['id' => 'abc-123'], [], 'HASH');
    }

    // --- extractRedisData: numberOfResults limit ---

    public function testSearchRespectsNumberOfResultsLimit(): void
    {
        $mock = $this->mockRedis();
        $mock->method('rawCommand')->willReturn([
            3,
            'TestIndex:1', ['id', '1', 'name', 'Alice'],
            'TestIndex:2', ['id', '2', 'name', 'Bob'],
            'TestIndex:3', ['id', '3', 'name', 'Charlie'],
        ]);

        $result = $this->makeClient($mock)->search('TestIndex', [], [], 'HASH', 2);

        $this->assertCount(2, $result);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame('Bob', $result[1]['name']);
    }
}
