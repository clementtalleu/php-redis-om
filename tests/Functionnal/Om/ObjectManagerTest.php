<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om;

use Talleu\RedisOm\Client\Helper\Converter;
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
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testPersistAndFlushHash(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $keys = $this->createRedisClient()->keys('*');
        $classNameConverted = Converter::prefix(DummyHash::class);
        $this->assertTrue(in_array($classNameConverted.':1', $keys));
        $this->assertTrue(in_array($classNameConverted.':2', $keys));
        $this->assertTrue(in_array($classNameConverted.':3', $keys));
    }

    public function testPersistAndFlushJson(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $keys = $this->createRedisClient()->keys('*');
        $classNameConverted = Converter::prefix(DummyJson::class);
        $this->assertTrue(in_array($classNameConverted.':1', $keys));
        $this->assertTrue(in_array($classNameConverted.':2', $keys));
        $this->assertTrue(in_array($classNameConverted.':3', $keys));
    }

    public function testFindJson()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(DummyJson::class);


        /** @var DummyJson $object */
        $object1 = $this->objectManager->find(DummyJson::class, 1);
        $this->assertInstanceOf(DummyJson::class, $object1);
        $this->assertEquals($object1, $dummies[0]);

        /** @var DummyJson $object */
        $object2 = $this->objectManager->find(DummyJson::class, 2);
        $this->assertEquals($object2, $dummies[1]);

        /** @var DummyJson $object */
        $object3 = $this->objectManager->find(DummyJson::class, 3);
        $this->assertEquals($object3, $dummies[2]);
    }

    public function testFindHash()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures();



        $object1 = $this->objectManager->find(DummyHash::class, 1);
        $this->assertInstanceOf(DummyHash::class, $object1);
        $this->assertEquals($object1, $dummies[0]);

        /** @var DummyJson $object */
        $object2 = $this->objectManager->find(DummyHash::class, 2);
        $this->assertEquals($object2, $dummies[1]);

        /** @var DummyJson $object */
        $object3 = $this->objectManager->find(DummyHash::class, 3);
        $this->assertEquals($object3, $dummies[2]);
    }

    public function testRemove()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures();
        /** @var DummyHash $object */
        $object = $dummies[0];


        $this->objectManager->persist($object);
        $this->objectManager->flush();

        $retrieveObject = $this->objectManager->find(DummyHash::class, $object->id);
        $this->assertInstanceOf(DummyHash::class, $retrieveObject);

        // Then remove the object
        $this->objectManager->remove($object);
        $this->objectManager->flush();
        $retrieveObject = $this->objectManager->find(DummyHash::class, $object->id);
        $this->assertNull($retrieveObject);
    }

    public function testGetRepository()
    {

        $repo = $this->objectManager->getRepository(DummyHash::class);
        $this->assertInstanceOf(HashRepository::class, $repo);

        $repo = $this->objectManager->getRepository(DummyJson::class);
        $this->assertInstanceOf(JsonRepository::class, $repo);
    }

    public function testClear()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(flush: false);

        foreach ($dummies as $dummy) {
            $this->objectManager->persist($dummy);
        }

        $this->objectManager->clear();
        $keys = $this->createRedisClient()->keys('*');
        $this->assertCount(0, $keys);
    }

    public function testDetach()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(flush: false);

        foreach ($dummies as $dummy) {
            $this->objectManager->persist($dummy);
        }

        $this->objectManager->detach($dummies[1]);
        $this->objectManager->flush();
        $keys = $this->createRedisClient()->keys('*');
        $this->assertCount(2, $keys);
        $this->assertNotContains("Talleu_RedisOm_Tests_Fixtures_Hash_DummyHash:2", $keys);
    }

    public function testContains()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(flush: false);

        foreach ($dummies as $dummy) {
            $this->objectManager->persist($dummy);
        }

        $this->assertTrue($this->objectManager->contains($dummies[0]));
        $this->assertTrue($this->objectManager->contains($dummies[1]));
        $this->assertTrue($this->objectManager->contains($dummies[2]));
    }
}
