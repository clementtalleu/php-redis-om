<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Client;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Tests\Client\Client;

final class RedisClientIntegrationTest extends TestCase
{
    private RedisClientInterface $client;

    protected function setUp(): void
    {
        $this->client = (new Client())->redisClient;
        $this->client->flushAll();
    }

    // --- scanKeys ---

    public function testScanKeysReturnsMatchingKeys(): void
    {
        $this->client->hMSet('ScanTest:1', ['id' => '1']);
        $this->client->hMSet('ScanTest:2', ['id' => '2']);
        $this->client->hMSet('OtherPrefix:1', ['id' => '99']);

        $keys = $this->client->scanKeys('ScanTest');

        $this->assertCount(2, $keys);
        $this->assertContains('ScanTest:1', $keys);
        $this->assertContains('ScanTest:2', $keys);
    }

    public function testScanKeysReturnsEmptyArrayWhenNoMatch(): void
    {
        $keys = $this->client->scanKeys('NonExistentPrefix_XYZ');

        $this->assertSame([], $keys);
    }

    // --- discard ---

    public function testDiscardResetsConnectionToNormalState(): void
    {
        $this->client->multi();
        $this->client->discard();

        $this->client->hMSet('AfterDiscard:1', ['id' => '1', 'name' => 'ok']);
        $result = $this->client->hGetAll('AfterDiscard:1');

        $this->assertSame('1', $result['id']);
        $this->assertSame('ok', $result['name']);
    }

    public function testDiscardCancelsQueuedWrites(): void
    {
        $this->client->multi();
        try {
            $this->client->hMSet('InTx:99', ['id' => '99']);
        } catch (\Throwable) {
            // some clients behave differently in MULTI mode
        }
        $this->client->discard();

        $keys = $this->client->scanKeys('InTx');
        $this->assertCount(0, $keys);
    }

    // --- jsonGetProperty ---

    public function testJsonGetPropertyReturnsSpecificField(): void
    {
        $this->client->jsonSet('JsonPropTest:1', '$', '{"id":"1","name":"Alice","age":30}');

        $raw = $this->client->jsonGetProperty('JsonPropTest:1', 'name');

        $this->assertNotNull($raw);
        $decoded = json_decode($raw, true);
        $this->assertSame('Alice', $decoded[0]);
    }

    public function testJsonGetPropertyReturnsNullWhenKeyMissing(): void
    {
        $this->assertNull($this->client->jsonGetProperty('NonExistent:99', 'name'));
    }

    public function testJsonGetPropertyReturnsEmptyForMissingField(): void
    {
        $this->client->jsonSet('JsonPropTest:2', '$', '{"id":"2"}');

        $result = $this->client->jsonGetProperty('JsonPropTest:2', 'nonexistent_field');

        if ($result !== null) {
            $this->assertSame([], json_decode($result, true));
        } else {
            $this->assertNull($result);
        }
    }

    // --- hSet / hget ---

    public function testHSetAndHGetWork(): void
    {
        $this->client->hMSet('HSetTest:1', ['id' => '1', 'name' => 'initial']);
        $this->client->hSet('HSetTest:1', 'name', 'updated');

        $this->assertSame('updated', $this->client->hget('HSetTest:1', 'name'));
    }

    // --- jsonGet / jsonSet round-trip ---

    public function testJsonSetAndGetRoundTrip(): void
    {
        $payload = '{"id":"42","name":"Bob"}';
        $this->client->jsonSet('JsonRoundTrip:42', '$', $payload);

        $result = $this->client->jsonGet('JsonRoundTrip:42');

        $this->assertNotNull($result);
        $decoded = json_decode($result, true);
        $this->assertSame('42', $decoded['id']);
        $this->assertSame('Bob', $decoded['name']);
    }

    public function testJsonGetReturnsNullForMissingKey(): void
    {
        $this->assertNull($this->client->jsonGet('NonExistent:99'));
    }

    // --- expireTime ---

    public function testExpireAndExpireTimeRoundTrip(): void
    {
        $this->client->hMSet('ExpireTest:1', ['id' => '1']);
        $this->client->expire('ExpireTest:1', 3600);

        $timestamp = $this->client->expireTime('ExpireTest:1');

        $this->assertGreaterThan(time(), $timestamp);
        $this->assertLessThanOrEqual(time() + 3601, $timestamp);
    }

    public function testExpireTimeReturnsNegativeOneWhenNoTtl(): void
    {
        $this->client->hMSet('NoTtl:1', ['id' => '1']);

        $this->assertSame(-1, $this->client->expireTime('NoTtl:1'));
    }

    // --- hGetAllMultiple ---

    public function testHGetAllMultipleReturnsBatchedHashes(): void
    {
        $this->client->hMSet('MultiHash:1', ['id' => '1', 'name' => 'Alice']);
        $this->client->hMSet('MultiHash:2', ['id' => '2', 'name' => 'Bob']);
        $this->client->hMSet('MultiHash:3', ['id' => '3', 'name' => 'Charlie']);

        $result = $this->client->hGetAllMultiple(['MultiHash:1', 'MultiHash:2', 'MultiHash:3']);

        $this->assertCount(3, $result);
        $this->assertSame('Alice', $result['MultiHash:1']['name']);
        $this->assertSame('Bob', $result['MultiHash:2']['name']);
        $this->assertSame('Charlie', $result['MultiHash:3']['name']);
    }

    public function testHGetAllMultipleSkipsMissingKeys(): void
    {
        $this->client->hMSet('MultiHash:1', ['id' => '1']);

        $result = $this->client->hGetAllMultiple(['MultiHash:1', 'MultiHash:NonExistent']);

        $this->assertArrayHasKey('MultiHash:1', $result);
        $this->assertArrayNotHasKey('MultiHash:NonExistent', $result);
    }

    // --- jsonGetMultiple ---

    public function testJsonGetMultipleReturnsBatchedDocuments(): void
    {
        $this->client->jsonSet('MultiJson:1', '$', '{"id":"1","name":"Alice"}');
        $this->client->jsonSet('MultiJson:2', '$', '{"id":"2","name":"Bob"}');

        $result = $this->client->jsonGetMultiple(['MultiJson:1', 'MultiJson:2']);

        $this->assertCount(2, $result);
        $this->assertStringContainsString('Alice', $result['MultiJson:1']);
        $this->assertStringContainsString('Bob', $result['MultiJson:2']);
    }

    public function testJsonGetMultipleReturnsNullForMissingKeys(): void
    {
        $this->client->jsonSet('MultiJson:1', '$', '{"id":"1"}');

        $result = $this->client->jsonGetMultiple(['MultiJson:1', 'MultiJson:NonExistent']);

        $this->assertNotNull($result['MultiJson:1']);
        $this->assertNull($result['MultiJson:NonExistent']);
    }
}
