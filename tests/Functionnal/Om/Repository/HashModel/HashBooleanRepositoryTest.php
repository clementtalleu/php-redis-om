<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class HashBooleanRepositoryTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createClient());
        parent::setUp();
    }

    public function testFindByEnabled()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['enabled' => true]);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertTrue($dummy->enabled);
        }
    }

    public function testFindOneByEnabled()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $objet = $repository->findOneBy(['enabled' => true]);
        $this->assertTrue($objet->enabled);
    }

    public function testFindByDisabled()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['enabled' => false]);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertFalse($dummy->enabled);
        }
    }

    public function testFindOneByDisabled()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $objet = $repository->findOneBy(['enabled' => false]);
        $this->assertFalse($objet->enabled);
    }
}
