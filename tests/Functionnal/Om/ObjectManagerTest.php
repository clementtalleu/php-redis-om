<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om;

use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class ObjectManagerTest extends RedisAbstractTestCase
{
    public function testPersistAndFlushHash(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(RedisFormat::HASH);

        $keys = static::createClient()->keys('*');
        $classNameConverted = RedisClient::convertPrefix(DummyHash::class);
        $this->assertTrue(in_array($classNameConverted.':1', $keys));
        $this->assertTrue(in_array($classNameConverted.':2', $keys));
        $this->assertTrue(in_array($classNameConverted.':3', $keys));
    }

    public function testPersistAndFlushJson(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(RedisFormat::JSON);

        $keys = static::createClient()->keys('*');
        $classNameConverted = RedisClient::convertPrefix(DummyJson::class);
        $this->assertTrue(in_array($classNameConverted.':1', $keys));
        $this->assertTrue(in_array($classNameConverted.':2', $keys));
        $this->assertTrue(in_array($classNameConverted.':3', $keys));
    }

    public function testFindJson()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(RedisFormat::JSON);

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
        $dummies = static::loadRedisFixtures(RedisFormat::HASH);

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
}
