<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\JsonModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJsonWithNullProperties;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class JsonNullableRepositoryTest extends RedisAbstractTestCase
{

    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createClient());
        parent::setUp(); 
    }

    public function testFindByNull()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJsonWithNullProperties::class);

        $repository = $this->objectManager->getRepository(DummyJsonWithNullProperties::class);

        // Update 1 object to set unknown to not null
        /** @var DummyJsonWithNullProperties $object */
        $object = $repository->findOneBy(['name' => 'Kevin']);
        $object->unknown = 'Not null';
        $this->objectManager->persist($object);
        $this->objectManager->flush();

        $collection = $repository->findBy(['unknown' => null]);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyJsonWithNullProperties::class, $dummy);
            $this->assertNull($dummy->unknown);
        }
    }

    public function testFindByNotNull()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJsonWithNullProperties::class);

        $repository = $this->objectManager->getRepository(DummyJsonWithNullProperties::class);

        // Update 1 object to set unknown to not null
        /** @var DummyJsonWithNullProperties $object */
        $object = $repository->findOneBy(['name' => 'Kevin']);
        $object->unknown = 'Notnull';
        $this->objectManager->persist($object);
        $this->objectManager->flush();

        $collection = $repository->findBy(['unknown' => 'Notnull']);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyJsonWithNullProperties::class, $dummy);
            $this->assertNotNull($dummy->unknown);
        }
    }
}
