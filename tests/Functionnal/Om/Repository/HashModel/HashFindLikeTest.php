<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class HashFindLikeTest extends RedisAbstractTestCase
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
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findByLike(['name' => 'Oli']);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertTrue(str_contains($dummy->name, 'Oli'));
        }
    }

    public function testFindOneByLike()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyHash::class);

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $object = $repository->findOneByLike(['name' => 'Oli']);
        $this->assertTrue(str_contains($object->name, 'Oli'));
    }

    public function testFindByLikeEndWith()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findByLike(['name' => 'vier']);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertTrue(str_contains($dummy->name, 'vier'));
        }
    }

    public function testFindOneByLikeEndWith()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $object = $repository->findOneByLike(['name' => 'vier']);
        $this->assertTrue(str_contains($object->name, 'vier'));
    }
}
