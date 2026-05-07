<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class StreamTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testStreamYieldsAllObjects(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collected = [];
        foreach ($repository->stream() as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $collected[] = $dummy;
        }

        $this->assertCount(3, $collected);
    }

    public function testStreamWithCriteria(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collected = [];
        foreach ($repository->stream(['name' => 'Olivier']) as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertSame('Olivier', $dummy->name);
            $collected[] = $dummy;
        }

        $this->assertCount(2, $collected);
    }

    public function testStreamWithOrderBy(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $ages = [];
        foreach ($repository->stream(orderBy: ['age' => 'ASC']) as $dummy) {
            $ages[] = $dummy->age;
        }

        $sorted = $ages;
        sort($sorted);
        $this->assertSame($sorted, $ages);
    }

    public function testStreamEmptyCollection(): void
    {
        static::emptyRedis();
        static::generateIndex();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collected = [];
        foreach ($repository->stream() as $dummy) {
            $collected[] = $dummy;
        }

        $this->assertCount(0, $collected);
    }

    public function testStreamWithSmallBatchSize(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collected = [];
        foreach ($repository->stream(batchSize: 1) as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $collected[] = $dummy;
        }

        $this->assertCount(3, $collected);
    }

    public function testStreamBatchSizeExactMultipleOfTotal(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collected = [];
        foreach ($repository->stream(batchSize: 3) as $dummy) {
            $collected[] = $dummy;
        }

        $this->assertCount(3, $collected);
    }

    public function testManagerStreamYieldsAllObjects(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $collected = [];
        foreach ($this->objectManager->stream(DummyHash::class) as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $collected[] = $dummy;
        }

        $this->assertCount(3, $collected);
    }

    public function testManagerStreamWithCriteria(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $collected = [];
        foreach ($this->objectManager->stream(DummyHash::class, ['name' => 'Olivier']) as $dummy) {
            $this->assertSame('Olivier', $dummy->name);
            $collected[] = $dummy;
        }

        $this->assertCount(2, $collected);
    }

    public function testManagerStreamClearsIdentityMapAfterLastBatch(): void
    {
        static::emptyRedis();
        static::generateIndex();
        $fixtures = static::loadRedisFixtures();

        $yielded = [];
        foreach ($this->objectManager->stream(DummyHash::class, batchSize: 1) as $dummy) {
            $yielded[] = $dummy;
        }

        $this->assertCount(3, $yielded);

        // Identity map was cleared after the last batch: find() returns a fresh instance
        $fresh = $this->objectManager->find(DummyHash::class, $fixtures[0]->id);
        $this->assertNotSame($yielded[0], $fresh);
    }
}
