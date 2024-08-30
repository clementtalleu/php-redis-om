<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Mapping;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHashWithPrivateProperties;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJsonWithPrivateProperties;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class PrivatePropertiesTest extends RedisAbstractTestCase
{

    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createClient());
        parent::setUp();
    }

    public function testPropertyNotPublic()
    {
        static::emptyRedis();
        static::generateIndex();

        $dummy = DummyHashWithPrivateProperties::create(id: 12, name: 'Joad', age: 37);

        $this->objectManager->persist($dummy);
        $this->objectManager->flush();

        /** @var DummyHashWithPrivateProperties|null $object */
        $object = $this->objectManager->find(DummyHashWithPrivateProperties::class, 12);
        $this->assertInstanceOf(DummyHashWithPrivateProperties::class, $object);
        $this->assertEquals($object, $dummy);
    }

    public function testPropertyNotPublicJson()
    {
        static::emptyRedis();
        static::generateIndex();

        $dummy = DummyJsonWithPrivateProperties::create(id: 12, name: 'Joad', age: 37);

        $this->objectManager->persist($dummy);
        $this->objectManager->flush();

        /** @var DummyJsonWithPrivateProperties|null $object */
        $object = $this->objectManager->find(DummyJsonWithPrivateProperties::class, 12);
        $this->assertInstanceOf(DummyJsonWithPrivateProperties::class, $object);
        $this->assertEquals($object, $dummy);
    }
}
