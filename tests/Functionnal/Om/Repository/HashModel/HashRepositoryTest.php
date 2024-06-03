<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class HashRepositoryTest extends RedisAbstractTestCase
{
    public function testFindAll()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(RedisFormat::HASH->value);

        $objectManager = new RedisObjectManager();
        $repository = $objectManager->getRepository(DummyHash::class);

        $collection = $repository->findAll();

        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
        }
    }

    public function testFindBy()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(RedisFormat::HASH->value);

        $objectManager = new RedisObjectManager();
        $repository = $objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['name' => 'Olivier']);

        $this->assertCount(2, $collection);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertEquals('Olivier', $dummy->name);
        }
    }

    public function testFindByOrder()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(RedisFormat::HASH->value);

        $objectManager = new RedisObjectManager();
        $repository = $objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['name' => 'Olivier'], ['age' => 'ASC']);
        $this->assertCount(2, $collection);

        foreach ($collection as $dummy) {
            if (isset($age)) {
                $this->assertGreaterThan($age, $dummy->age);
            }
            $age = $dummy->age;
        }
        unset($age);

        $collection = $repository->findBy(['name' => 'Olivier'], ['age' => 'DESC']);
        $this->assertCount(2, $collection);
        foreach ($collection as $dummy) {
            if (isset($age)) {
                $this->assertLessThan($age, $dummy->age);
            }
            $age = $dummy->age;
        }
    }

    public function testFindByMultiCriterias()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(RedisFormat::HASH->value);

        $objectManager = new RedisObjectManager();
        $repository = $objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['name' => 'Olivier', 'age' => 34]);

        $this->assertCount(1, $collection);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertEquals('Olivier', $dummy->name);
            $this->assertEquals('34', $dummy->age);
        }
    }

    public function testFindOneBy()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(RedisFormat::HASH->value);

        $objectManager = new RedisObjectManager();
        $repository = $objectManager->getRepository(DummyHash::class);

        $object = $repository->findOneBy(['age' => 34]);
        $this->assertInstanceOf(DummyHash::class, $object);
        $this->assertEquals('34', $object->age);
    }
}
