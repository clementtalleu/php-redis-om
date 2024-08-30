<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class HashIdentifierRepositoryTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createClient());
        parent::setUp();
    }

    public function testFindById()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();


        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['id' => 1]);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertEquals($dummy->id, 1);
        }
    }

    public function testFindOneById()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();


        $repository = $this->objectManager->getRepository(DummyHash::class);

        $objet = $repository->findOneBy(['id' => 2]);
        $this->assertEquals($objet->id, 2);
    }
}
