<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om;

use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Om\Repository\HashModel\HashRepository;
use Talleu\RedisOm\Om\Repository\JsonModel\JsonRepository;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class ObjectManagerTest extends RedisAbstractTestCase
{
    public function testPersistAndFlushHash(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $keys = $this->createClient()->keys('*');
        $classNameConverted = RedisClient::convertPrefix(DummyHash::class);
        $this->assertTrue(in_array($classNameConverted.':1', $keys));
        $this->assertTrue(in_array($classNameConverted.':2', $keys));
        $this->assertTrue(in_array($classNameConverted.':3', $keys));
    }

    public function testPersistAndFlushJson(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $keys = $this->createClient()->keys('*');
        $classNameConverted = RedisClient::convertPrefix(DummyJson::class);
        $this->assertTrue(in_array($classNameConverted.':1', $keys));
        $this->assertTrue(in_array($classNameConverted.':2', $keys));
        $this->assertTrue(in_array($classNameConverted.':3', $keys));
    }

    public function testFindJson()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(DummyJson::class);

        $objectManager = new RedisObjectManager();
        /** @var DummyJson $object */
        $object1 = $objectManager->find(DummyJson::class, 1);
        $this->assertInstanceOf(DummyJson::class, $object1);
        $this->assertEquals($object1, $dummies[0]);

        /** @var DummyJson $object */
        $object2 = $objectManager->find(DummyJson::class, 2);
        $this->assertEquals($object2, $dummies[1]);

        /** @var DummyJson $object */
        $object3 = $objectManager->find(DummyJson::class, 3);
        $this->assertEquals($object3, $dummies[2]);
    }

    public function testFindHash()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures();

        $objectManager = new RedisObjectManager();

        $object1 = $objectManager->find(DummyHash::class, 1);
        $this->assertInstanceOf(DummyHash::class, $object1);
        $this->assertEquals($object1, $dummies[0]);

        /** @var DummyJson $object */
        $object2 = $objectManager->find(DummyHash::class, 2);
        $this->assertEquals($object2, $dummies[1]);

        /** @var DummyJson $object */
        $object3 = $objectManager->find(DummyHash::class, 3);
        $this->assertEquals($object3, $dummies[2]);
    }

    public function testRemove()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures();
        /** @var DummyHash $object */
        $object = $dummies[0];

        $objectManager = new RedisObjectManager();
        $objectManager->persist($object);
        $objectManager->flush();

        $retrieveObject = $objectManager->find(DummyHash::class, $object->id);
        $this->assertInstanceOf(DummyHash::class, $retrieveObject);

        // Then remove the object
        $objectManager->remove($object);
        $objectManager->flush();
        $retrieveObject = $objectManager->find(DummyHash::class, $object->id);
        $this->assertNull($retrieveObject);
    }

    public function testGetRepository()
    {
        $objectManager = new RedisObjectManager();
        $repo = $objectManager->getRepository(DummyHash::class);
        $this->assertInstanceOf(HashRepository::class, $repo);

        $repo = $objectManager->getRepository(DummyJson::class);
        $this->assertInstanceOf(JsonRepository::class, $repo);
    }

    public function testClear()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(flush: false);
        $objectManager = new RedisObjectManager();
        foreach ($dummies as $dummy) {
            $objectManager->persist($dummy);
        }

        $objectManager->clear();
        $keys = $this->createClient()->keys('*');
        $this->assertCount(0, $keys);
    }

    public function testDetach()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(flush: false);
        $objectManager = new RedisObjectManager();
        foreach ($dummies as $dummy) {
            $objectManager->persist($dummy);
        }

        $objectManager->detach($dummies[1]);
        $objectManager->flush();
        $keys = $this->createClient()->keys('*');
        $this->assertCount(2, $keys);
        $this->assertNotContains("Talleu_RedisOm_Tests_Fixtures_Hash_DummyHash:2", $keys);
    }
}
