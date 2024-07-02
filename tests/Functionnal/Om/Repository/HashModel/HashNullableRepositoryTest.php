<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHashWithNullProperties;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class HashNullableRepositoryTest extends RedisAbstractTestCase
{
    public function testFindByNull()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyHashWithNullProperties::class);

        $objectManager = new RedisObjectManager();
        $repository = $objectManager->getRepository(DummyHashWithNullProperties::class);

        // Update 1 object to set unknown to not null
        /** @var DummyHashWithNullProperties $object */
        $object = $repository->findOneBy(['name' => 'Kevin']);
        $object->unknown = 'Not null';
        $objectManager->persist($object);
        $objectManager->flush();

        $collection = $repository->findBy(['unknown' => null]);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHashWithNullProperties::class, $dummy);
            $this->assertNull($dummy->unknown);
        }
    }

    public function testFindByNotNull()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyHashWithNullProperties::class);

        $objectManager = new RedisObjectManager();
        $repository = $objectManager->getRepository(DummyHashWithNullProperties::class);

        // Update 1 object to set unknown to not null
        /** @var DummyHashWithNullProperties $object */
        $object = $repository->findOneBy(['name' => 'Kevin']);
        $object->unknown = 'Notnull';
        $objectManager->persist($object);
        $objectManager->flush();

        $collection = $repository->findBy(['unknown' => 'Notnull']);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHashWithNullProperties::class, $dummy);
            $this->assertNotNull($dummy->unknown);
        }
    }
}
