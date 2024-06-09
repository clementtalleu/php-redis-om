<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Mapping;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHashWithoutAge;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJsonWithoutAge;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class MappingPropertyTest extends RedisAbstractTestCase
{
    public function testPropertyNotMappedJson()
    {
        static::emptyRedis();
        static::generateIndex();

        $dummyJson = DummyJsonWithoutAge::create(id: 33, price: 10.5, age: null, name: 'Clément');
        $objectManager = new RedisObjectManager();
        $objectManager->persist($dummyJson);
        $objectManager->flush();

        /** @var DummyJsonWithoutAge $object */
        $object = $objectManager->find(DummyJsonWithoutAge::class, 33);
        $this->assertInstanceOf(DummyJsonWithoutAge::class, $object);
        $this->assertNull($object->age);
        $this->assertEquals('Clément', $object->name);
    }

    public function testPropertyNotMappedHash()
    {
        static::emptyRedis();
        static::generateIndex();

        $dummyJson = DummyHashWithoutAge::create(id: 33, price: 10.5, age: null, name: 'Clément');
        $objectManager = new RedisObjectManager();
        $objectManager->persist($dummyJson);
        $objectManager->flush();

        /** @var DummyHashWithoutAge $object */
        $object = $objectManager->find(DummyHashWithoutAge::class, 33);
        $this->assertInstanceOf(DummyHashWithoutAge::class, $object);
        $this->assertNull($object->age);
        $this->assertEquals('Clément', $object->name);
    }
}
