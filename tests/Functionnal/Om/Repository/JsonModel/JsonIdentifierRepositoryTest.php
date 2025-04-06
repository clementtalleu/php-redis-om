<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\JsonModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class JsonIdentifierRepositoryTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testFindById()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);

        $collection = $repository->findBy(['id' => 1]);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $this->assertEquals($dummy->id, 1);
        }
    }

    public function testFindOneById()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);

        $objet = $repository->findOneBy(['id' => 2]);
        $this->assertEquals($objet->id, 2);
    }
}
