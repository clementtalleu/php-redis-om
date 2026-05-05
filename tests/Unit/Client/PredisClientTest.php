<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use Predis\Client as PredisLib;
use Predis\Pipeline\Pipeline;
use Talleu\RedisOm\Client\PredisClient;
use Talleu\RedisOm\Exception\RedisClientResponseException;
use Talleu\RedisOm\Om\Mapping\Property;

final class PredisClientTest extends TestCase
{
    private function makeClient(PredisLib $predis): PredisClient
    {
        return new PredisClient($predis);
    }

    private function mockPredis(): PredisLib
    {
        return $this->getMockBuilder(PredisLib::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['executeRaw', 'pipeline'])
            ->addMethods([
                'hmset', 'hset', 'hgetall', 'hget', 'del',
                'flushall', 'expire', 'expiretime', 'keys',
                'multi', 'exec', 'discard', 'scan',
            ])
            ->getMock();
    }

    private function mockPipeline(): Pipeline
    {
        return $this->getMockBuilder(Pipeline::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->addMethods(['hgetall'])
            ->getMock();
    }

    // --- hMSet ---

    public function testHMSetThrowsOnRedisError(): void
    {
        $mock = $this->mockPredis();
        $mock->method('hmset')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->hMSet('key', ['field' => 'value']);
    }

    public function testHMSetSucceeds(): void
    {
        $mock = $this->mockPredis();
        $mock->expects($this->once())->method('hmset')->willReturn(true);

        $this->makeClient($mock)->hMSet('key', ['field' => 'value']);
    }

    // --- hget ---

    public function testHgetThrowsWhenResultIsNull(): void
    {
        $mock = $this->mockPredis();
        $mock->method('hget')->willReturn(null);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->hget('key', 'field');
    }

    public function testHgetReturnsValue(): void
    {
        $mock = $this->mockPredis();
        $mock->method('hget')->willReturn('hello');

        $this->assertSame('hello', $this->makeClient($mock)->hget('key', 'field'));
    }

    // --- hSet ---

    public function testHSetCallsPredisHSet(): void
    {
        $mock = $this->mockPredis();
        $mock->expects($this->once())->method('hset')->with('key', 'field', 'value');

        $this->makeClient($mock)->hSet('key', 'field', 'value');
    }

    // --- hGetAll ---

    public function testHGetAllReturnsArray(): void
    {
        $mock = $this->mockPredis();
        $mock->method('hgetall')->willReturn(['id' => '1', 'name' => 'test']);

        $this->assertSame(['id' => '1', 'name' => 'test'], $this->makeClient($mock)->hGetAll('key'));
    }

    // --- del ---

    public function testDelThrowsOnRedisError(): void
    {
        $mock = $this->mockPredis();
        $mock->method('del')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->del('key');
    }

    // --- jsonGet ---

    public function testJsonGetReturnsNullWhenKeyNotFound(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->assertNull($this->makeClient($mock)->jsonGet('key'));
    }

    public function testJsonGetReturnsJsonString(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn('{"id":"1"}');

        $this->assertSame('{"id":"1"}', $this->makeClient($mock)->jsonGet('key'));
    }

    // --- jsonGetProperty ---

    public function testJsonGetPropertyReturnsNullWhenKeyNotFound(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->assertNull($this->makeClient($mock)->jsonGetProperty('key', 'field'));
    }

    public function testJsonGetPropertyReturnsValue(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn('["hello"]');

        $this->assertSame('["hello"]', $this->makeClient($mock)->jsonGetProperty('key', 'field'));
    }

    // --- jsonSet ---

    public function testJsonSetThrowsOnRedisError(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->jsonSet('key', '$', '{}');
    }

    // --- jsonSetProperty ---

    public function testJsonSetPropertyCallsExecuteRaw(): void
    {
        $mock = $this->mockPredis();
        $mock->expects($this->once())->method('executeRaw');

        $this->makeClient($mock)->jsonSetProperty('key', 'field', '"value"');
    }

    // --- jsonDel ---

    public function testJsonDelThrowsOnRedisError(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->jsonDel('key');
    }

    // --- jsonMSet ---

    public function testJsonMSetThrowsOnInvalidParamCount(): void
    {
        $mock = $this->mockPredis();

        $this->expectException(\InvalidArgumentException::class);
        $this->makeClient($mock)->jsonMSet(['key', '$']); // count=2, not multiple of 3
    }

    public function testJsonMSetThrowsOnRedisError(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->jsonMSet(['key', '$', '{}']);
    }

    // --- createIndex ---

    public function testCreateIndexDoesNothingWhenPropertiesEmpty(): void
    {
        $mock = $this->mockPredis();
        $mock->expects($this->never())->method('executeRaw');

        $this->makeClient($mock)->createIndex('TestIndex', 'HASH', []);
    }

    public function testCreateIndexThrowsOnRedisError(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $property = (object)['name' => 'name', 'indexName' => 'name', 'indexType' => Property::INDEX_TAG];

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->createIndex('TestIndex', 'HASH', [$property]);
    }

    public function testCreateIndexHandlesTimestampProperty(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $flat = implode(' ', $args);
            $this->assertStringContainsString('created_at#timestamp', $flat);
            return true;
        });

        $property = (object)[
            'name' => 'created_at#timestamp',
            'indexName' => 'created_at#timestamp',
            'indexType' => 'NUMERIC',
        ];

        $this->makeClient($mock)->createIndex('TestIndex', 'HASH', [$property]);
    }

    // --- dropIndex ---

    public function testDropIndexReturnsFalseOnRedisException(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willThrowException(new \RedisException('ERR no such index'));

        $this->assertFalse($this->makeClient($mock)->dropIndex('NonExistentIndex'));
    }

    public function testDropIndexReturnsTrueOnSuccess(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn('OK');

        $this->assertTrue($this->makeClient($mock)->dropIndex('ExistingIndex'));
    }

    // --- count ---

    public function testCountThrowsOnRedisError(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->count('TestIndex', ['field' => 'value']);
    }

    public function testCountReturnsZeroWhenNoResults(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([0]);

        $this->assertSame(0, $this->makeClient($mock)->count('TestIndex'));
    }

    public function testCountReturnsCorrectCount(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([2, 'key1', [], 'key2', []]);

        $this->assertSame(2, $this->makeClient($mock)->count('TestIndex'));
    }

    public function testCountWithTagCriteriaBuildsCorrectQuery(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $this->assertStringContainsString('@name:{Alice}', implode(' ', $args));
            return [1, 'key1', []];
        });

        $this->makeClient($mock)->count('TestIndex', ['name' => 'Alice'], Property::INDEX_TAG);
    }

    public function testCountWithNumericSearchType(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $query = implode(' ', $args);
            $this->assertStringContainsString('@age:30', $query);
            $this->assertStringNotContainsString('{', $query);
            return [1, 'key1', []];
        });

        $this->makeClient($mock)->count('TestIndex', ['age' => '30'], Property::INDEX_NUMERIC);
    }

    public function testCountEscapesDashInCriteriaValue(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $this->assertStringContainsString('\-', implode(' ', $args));
            return [1, 'key1', []];
        });

        $this->makeClient($mock)->count('TestIndex', ['id' => '550e8400-e29b-41d4-a716-446655440000'], Property::INDEX_TAG);
    }

    public function testCountWithRangeFilters(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $this->assertStringContainsString('@price:[10 50]', implode(' ', $args));
            return [1, 'key1', []];
        });

        $this->makeClient($mock)->count('TestIndex', [], null, ['@price:[10 50]']);
    }

    // --- scanKeys ---

    public function testScanKeysReturnsKeysOnFirstIteration(): void
    {
        $mock = $this->mockPredis();
        $mock->method('scan')->willReturn([0, ['ScanTest:1', 'ScanTest:2']]);

        $result = $this->makeClient($mock)->scanKeys('ScanTest');

        $this->assertCount(2, $result);
        $this->assertContains('ScanTest:1', $result);
        $this->assertContains('ScanTest:2', $result);
    }

    public function testScanKeysReturnsEmptyWhenNoMatch(): void
    {
        $mock = $this->mockPredis();
        $mock->method('scan')->willReturn([0, []]);

        $this->assertSame([], $this->makeClient($mock)->scanKeys('NonExistent'));
    }

    public function testScanKeysIteratesUntilCursorZero(): void
    {
        $mock = $this->mockPredis();
        $mock->method('scan')->willReturnOnConsecutiveCalls(
            [1, ['key:1']],
            [0, ['key:2']],
        );

        $result = $this->makeClient($mock)->scanKeys('key');

        $this->assertCount(2, $result);
        $this->assertContains('key:1', $result);
        $this->assertContains('key:2', $result);
    }

    // --- flushAll ---

    public function testFlushAllThrowsOnRedisError(): void
    {
        $mock = $this->mockPredis();
        $mock->method('flushall')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->flushAll();
    }

    // --- expire ---

    public function testExpireThrowsOnRedisError(): void
    {
        $mock = $this->mockPredis();
        $mock->method('expire')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->expire('key', 3600);
    }

    // --- expireTime ---

    public function testExpireTimeReturnsTimestamp(): void
    {
        $mock = $this->mockPredis();
        $mock->method('expiretime')->willReturn(1700000000);

        $this->assertSame(1700000000, $this->makeClient($mock)->expireTime('key'));
    }

    // --- hGetAllMultiple ---

    public function testHGetAllMultipleReturnsPipelineResults(): void
    {
        $pipeline = $this->mockPipeline();
        $pipeline->method('execute')->willReturn([
            ['id' => '1', 'name' => 'Alice'],
            [],
            ['id' => '3', 'name' => 'Charlie'],
        ]);

        $mock = $this->mockPredis();
        $mock->method('pipeline')->willReturn($pipeline);

        $result = $this->makeClient($mock)->hGetAllMultiple(['Key:1', 'Key:2', 'Key:3']);

        $this->assertCount(2, $result);
        $this->assertSame(['id' => '1', 'name' => 'Alice'], $result['Key:1']);
        $this->assertSame(['id' => '3', 'name' => 'Charlie'], $result['Key:3']);
        $this->assertArrayNotHasKey('Key:2', $result);
    }

    // --- jsonGetMultiple ---

    public function testJsonGetMultipleReturnsPipelineResults(): void
    {
        $mock = $this->mockPredis();
        // pipeline() with callback returns the results directly
        $mock->method('pipeline')->willReturn(['{"id":"1"}', false]);

        $result = $this->makeClient($mock)->jsonGetMultiple(['Key:1', 'Key:2']);

        $this->assertSame('{"id":"1"}', $result['Key:1']);
        $this->assertNull($result['Key:2']);
    }

    // --- multi / exec / discard ---

    public function testMultiCallsPredisMulti(): void
    {
        $mock = $this->mockPredis();
        $mock->expects($this->once())->method('multi');

        $this->makeClient($mock)->multi();
    }

    public function testExecCallsPredisExec(): void
    {
        $mock = $this->mockPredis();
        $mock->expects($this->once())->method('exec');

        $this->makeClient($mock)->exec();
    }

    public function testDiscardCallsPredisDiscard(): void
    {
        $mock = $this->mockPredis();
        $mock->expects($this->once())->method('discard');

        $this->makeClient($mock)->discard();
    }

    // --- keys ---

    public function testKeysReturnsMatchingKeys(): void
    {
        $mock = $this->mockPredis();
        $mock->method('keys')->willReturn(['key:1', 'key:2']);

        $this->assertSame(['key:1', 'key:2'], $this->makeClient($mock)->keys('key:*'));
    }

    // --- getIndexInfo ---

    public function testGetIndexInfoReturnsEmptyArrayWhenFalse(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->assertSame([], $this->makeClient($mock)->getIndexInfo('TestIndex'));
    }

    public function testGetIndexInfoReturnsEmptyArrayWhenNoAttributesKey(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(['index_name', 'TestIndex', 'num_docs', '0']);

        $this->assertSame([], $this->makeClient($mock)->getIndexInfo('TestIndex'));
    }

    public function testGetIndexInfoParsesAttributes(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([
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
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->search('TestIndex', [], [], 'HASH');
    }

    public function testSearchThrowsOnRedisException(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willThrowException(new \RedisException('ERR index not found'));

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->search('TestIndex', [], [], 'HASH');
    }

    public function testSearchReturnsEmptyWhenCountIsZero(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([0]);

        $this->assertSame([], $this->makeClient($mock)->search('TestIndex', [], [], 'HASH'));
    }

    public function testSearchExtractsHashData(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([
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
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([
            1,
            'TestIndex:1',
            ['$', '{"id":"1","name":"Alice"}'],
        ]);

        $result = $this->makeClient($mock)->search('TestIndex', [], [], 'JSON');

        $this->assertCount(1, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('Alice', $result[0]['name']);
    }

    public function testSearchWithTagCriteria(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $this->assertStringContainsString('@name:{Alice}', implode(' ', $args));
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', ['name' => 'Alice'], [], 'HASH', null, 0, Property::INDEX_TAG);
    }

    public function testSearchWithNumericCriteria(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $query = implode(' ', $args);
            $this->assertStringContainsString('@age:30', $query);
            $this->assertStringNotContainsString('{', $query);
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', ['age' => '30'], [], 'HASH', null, 0, Property::INDEX_NUMERIC);
    }

    public function testSearchWithOrderBy(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $flat = implode(' ', $args);
            $this->assertStringContainsString('SORTBY', $flat);
            $this->assertStringContainsString('ASC', $flat);
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', [], ['name' => 'ASC'], 'HASH');
    }

    public function testSearchWithLimit(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $this->assertStringContainsString('LIMIT', implode(' ', $args));
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', [], [], 'HASH', 10, 20);
    }

    public function testSearchWithRangeFilters(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $this->assertStringContainsString('@price:[10 50]', implode(' ', $args));
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', [], [], 'HASH', null, 0, null, ['@price:[10 50]']);
    }

    public function testSearchEscapesDashInCriteria(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturnCallback(function (array $args) {
            $this->assertStringContainsString('\-', implode(' ', $args));
            return [0];
        });

        $this->makeClient($mock)->search('TestIndex', ['id' => 'abc-123'], [], 'HASH');
    }

    public function testSearchRespectsNumberOfResultsLimit(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([
            3,
            'TestIndex:1', ['id', '1', 'name', 'Alice'],
            'TestIndex:2', ['id', '2', 'name', 'Bob'],
            'TestIndex:3', ['id', '3', 'name', 'Charlie'],
        ]);

        $result = $this->makeClient($mock)->search('TestIndex', [], [], 'HASH', 2);

        $this->assertCount(2, $result);
    }

    // --- customSearch ---

    public function testCustomSearchThrowsOnFalseResult(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->customSearch('TestIndex', '*', 'HASH');
    }

    public function testCustomSearchThrowsOnRedisException(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willThrowException(new \RedisException('ERR'));

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->customSearch('TestIndex', '*', 'HASH');
    }

    public function testCustomSearchReturnsEmptyWhenCountIsZero(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([0]);

        $this->assertSame([], $this->makeClient($mock)->customSearch('TestIndex', '*', 'HASH'));
    }

    public function testCustomSearchExtractsHashData(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([
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
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn(false);

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->searchLike('TestIndex', 'Alice', 'HASH');
    }

    public function testSearchLikeThrowsOnRedisException(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willThrowException(new \RedisException('ERR'));

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->searchLike('TestIndex', 'Alice', 'HASH');
    }

    public function testSearchLikeReturnsEmptyWhenCountIsZero(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([0]);

        $this->assertSame([], $this->makeClient($mock)->searchLike('TestIndex', 'Alice', 'HASH'));
    }

    public function testSearchLikeExtractsHashData(): void
    {
        $mock = $this->mockPredis();
        $mock->method('executeRaw')->willReturn([
            1,
            'TestIndex:1',
            ['id', '1', 'name', 'Alice'],
        ]);

        $result = $this->makeClient($mock)->searchLike('TestIndex', 'Alice', 'HASH');

        $this->assertCount(1, $result);
        $this->assertSame('Alice', $result[0]['name']);
    }

    public function testSearchLikeThrowsWhenResultIsNotArray(): void
    {
        $mock = $this->mockPredis();
        // Non-false, non-array response triggers the extra guard branch
        $mock->method('executeRaw')->willReturn('unexpected_string');

        $this->expectException(RedisClientResponseException::class);
        $this->makeClient($mock)->searchLike('TestIndex', 'Alice', 'HASH');
    }

    public function testGetLastErrorCatchesExceptionFromExecuteRaw(): void
    {
        $mock = $this->mockPredis();
        // Main call (hMSet) returns false → triggers handleError → calls getLastError
        // getLastError calls executeRaw(['INVALID_COMMAND']) → throws → catch block hit
        $mock->method('hmset')->willReturn(false);
        $mock->method('executeRaw')->willReturnCallback(function (array $args): mixed {
            throw new \Exception('ERR unknown command \'' . $args[0] . '\'');
        });

        $this->expectException(RedisClientResponseException::class);
        $this->expectExceptionMessageMatches('/ERR unknown command/');
        $this->makeClient($mock)->hMSet('key', ['field' => 'value']);
    }
}
