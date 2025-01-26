<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\JsonModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class SearchByDateTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createClient());
    }

    public function testFindOneBy()
    {
        static::emptyRedis();
        static::generateIndex();
        $collection = static::loadRedisFixtures(DummyJson::class);


        $repository = $this->objectManager->getRepository(DummyJson::class);

        // An existing date
        $createdAt = new \DateTime('2022-01-01 00:00:00');
        $object = $repository->findOneBy(['createdAt' => $createdAt]);
        $this->assertInstanceOf(DummyJson::class, $object);
        $this->assertEquals($object->createdAt, $createdAt);
        $this->assertEquals($object, $collection[0]);

        // Another one
        $createdAt = new \DateTime('2018-05-01');
        $object = $repository->findOneBy(['createdAt' => $createdAt]);
        $this->assertInstanceOf(DummyJson::class, $object);
        $this->assertEquals($object->createdAt, $createdAt);
        $this->assertEquals($object, $collection[1]);

        // A date that does not exist
        $createdAt = new \DateTime('2000-05-01');
        $object = $repository->findOneBy(['createdAt' => $createdAt]);
        $this->assertNull($object);

        // A date existing but search by datetimeImmutable
        $createdAt = new \DateTimeImmutable('2014-02-12 00:00:00');
        $object = $repository->findOneBy(['createdAt' => $createdAt]);
        $this->assertInstanceOf(DummyJson::class, $object);
        $this->assertEquals($object->createdAt, $createdAt);
    }

    public function testFindBy()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);


        $repository = $this->objectManager->getRepository(DummyJson::class);

        // An existing date
        $createdAt = new \DateTime('2022-01-01 00:00:00');
        $collection = $repository->findBy(['createdAt' => $createdAt]);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $this->assertEquals($dummy->createdAt, $createdAt);
        }

        // Another one with immutable
        $createdAt = new \DateTimeImmutable('2018-05-01');
        $collection = $repository->findBy(['createdAt' => $createdAt]);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $this->assertEquals($dummy->createdAt, $createdAt);
        }

        // A date that does not exist
        $createdAt = new \DateTime('2000-05-01');
        $collection = $repository->findBy(['createdAt' => $createdAt]);
        $this->assertEmpty($collection);
    }

    public function testFindOneByWithString()
    {
        static::emptyRedis();
        static::generateIndex();
        $collection = static::loadRedisFixtures(DummyJson::class);


        $repository = $this->objectManager->getRepository(DummyJson::class);

        // An existing date
        $createdAt = new \DateTime('2022-01-01 00:00:00');
        $object = $repository->findOneBy(['createdAt' => '2022-01-01 00:00:00']);
        $this->assertInstanceOf(DummyJson::class, $object);
        $this->assertEquals($object->createdAt, $createdAt);
        $this->assertEquals($object, $collection[0]);

        // Another one
        $createdAt = new \DateTime('2018-05-01');
        $object = $repository->findOneBy(['createdAt' => '2018-05-01']);
        $this->assertInstanceOf(DummyJson::class, $object);
        $this->assertEquals($object->createdAt, $createdAt);
        $this->assertEquals($object, $collection[1]);

        // A date that does not exist
        $object = $repository->findOneBy(['createdAt' => '2000-05-01']);
        $this->assertNull($object);
    }

    public function testFindByString()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);


        $repository = $this->objectManager->getRepository(DummyJson::class);

        // An existing date
        $createdAt = new \DateTime('2022-01-01 00:00:00');
        $collection = $repository->findBy(['createdAt' => '2022-01-01 00:00:00']);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $this->assertEquals($dummy->createdAt, $createdAt);
        }

        // Another one with immutable
        $createdAt = new \DateTimeImmutable('2018-05-01');
        $collection = $repository->findBy(['createdAt' => '2018-05-01']);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyJson::class, $dummy);
            $this->assertEquals($dummy->createdAt, $createdAt);
        }

        // A date that does not exist
        $collection = $repository->findBy(['createdAt' => '2000-05-01']);
        $this->assertEmpty($collection);
    }
}
