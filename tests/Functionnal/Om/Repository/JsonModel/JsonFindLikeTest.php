<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\JsonModel;

use Talleu\RedisOm\Exception\BadPropertyException;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class JsonFindLikeTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createClient());
        parent::setUp();
    }

    public function testFindByLike()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);

        $collection = $repository->findByLike(['name' => 'Oli']);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $this->assertTrue(str_contains($dummy->name, 'Oli'));
        }
    }

    public function testFindOneByLike()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);

        $object = $repository->findOneByLike(['name' => 'Oli']);
        $this->assertTrue(str_contains($object->name, 'Oli'));
    }

    public function testFindByLikeEndWith()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);

        $collection = $repository->findByLike(['name' => 'vier']);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $this->assertTrue(str_contains($dummy->name, 'vier'));
        }
    }

    public function testFindOneByLikeEndWith()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);

        $object = $repository->findOneByLike(['name' => 'vier']);
        $this->assertTrue(str_contains($object->name, 'vier'));
    }
}
