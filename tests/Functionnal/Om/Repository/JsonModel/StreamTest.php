<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\JsonModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
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
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);

        $collected = [];
        foreach ($repository->stream() as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $collected[] = $dummy;
        }

        $this->assertCount(3, $collected);
    }

    public function testStreamWithCriteria(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);

        $collected = [];
        foreach ($repository->stream(['name' => 'Olivier']) as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $this->assertSame('Olivier', $dummy->name);
            $collected[] = $dummy;
        }

        $this->assertCount(2, $collected);
    }

    public function testStreamEmptyCollection(): void
    {
        static::emptyRedis();
        static::generateIndex();

        $repository = $this->objectManager->getRepository(DummyJson::class);

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
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);

        $collected = [];
        foreach ($repository->stream(batchSize: 1) as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $collected[] = $dummy;
        }

        $this->assertCount(3, $collected);
    }

    public function testManagerStreamYieldsAllObjects(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $collected = [];
        foreach ($this->objectManager->stream(DummyJson::class) as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $collected[] = $dummy;
        }

        $this->assertCount(3, $collected);
    }

    public function testManagerStreamClearsIdentityMapAfterLastBatch(): void
    {
        static::emptyRedis();
        static::generateIndex();
        $fixtures = static::loadRedisFixtures(DummyJson::class);

        $yielded = [];
        foreach ($this->objectManager->stream(DummyJson::class, batchSize: 1) as $dummy) {
            $yielded[] = $dummy;
        }

        $this->assertCount(3, $yielded);

        $fresh = $this->objectManager->find(DummyJson::class, $fixtures[0]->id);
        $this->assertNotSame($yielded[0], $fresh);
    }
}
