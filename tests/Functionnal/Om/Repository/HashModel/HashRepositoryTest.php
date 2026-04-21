<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Exception\BadIdentifierConfigurationException;
use Talleu\RedisOm\Exception\BadPropertyException;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Bar;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class HashRepositoryTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testFindAll()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findAll();

        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
        }
    }

    public function testFindBy()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['name' => 'Olivier']);

        $this->assertCount(2, $collection);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertEquals('Olivier', $dummy->name);
        }
    }

    public function testFindByTypo()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['name' => 'Lolivier']);
        $this->assertEmpty($collection);
    }

    public function testFindByOrder()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

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
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['name' => 'Olivier', 'age' => 34]);

        $this->assertCount(1, $collection);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertEquals('Olivier', $dummy->name);
            $this->assertEquals('34', $dummy->age);
        }
    }

    public function testFindLike()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findLike('olivier');

        $this->assertCount(2, $collection);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertEquals('Olivier', $dummy->name);
        }
    }

    public function testFindOneBy()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $object = $repository->findOneBy(['age' => 34]);
        $this->assertInstanceOf(DummyHash::class, $object);
        $this->assertEquals('34', $object->age);
    }

    public function testGetPropertyValue()
    {
        static::emptyRedis();
        static::generateIndex();
        $collection = static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $value = $repository->getPropertyValue(1, 'createdAt');
        $value1 = $repository->getPropertyValue(2, 'createdAt');
        $value2 = $repository->getPropertyValue(3, 'createdAt');
        $this->assertEquals($value, $collection[0]->createdAt);
        $this->assertEquals($value1, $collection[1]->createdAt);
        $this->assertEquals($value2, $collection[2]->createdAt);

        $value = $repository->getPropertyValue(1, 'createdAtImmutable');
        $this->assertEquals($value, $collection[0]->createdAtImmutable);

        $value = $repository->getPropertyValue(1, 'price');
        $this->assertEquals($value, $collection[0]->price);
    }

    public function testGetPropertyValueNotScalarType()
    {
        static::emptyRedis();
        static::generateIndex();
        $collection = static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $this->expectException(BadPropertyException::class);
        $repository->getPropertyValue(1, 'bar');
    }

    public function testGetPropertyValuePropertyDoesNotExist()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $this->expectException(BadPropertyException::class);
        $repository->getPropertyValue(1, 'test');
    }

    public function testFindByNestedObjectProperty()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['bar_title' => 'Hello']);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertEquals($dummy->bar->title, 'Hello');
        }
    }

    public function testFindByNestedObjecId()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $collection = $repository->findBy(['bar_id' => 2]);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertEquals($dummy->bar->id, 2);
        }
    }

    public function testFindOneByNestedObjectId()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $object = $repository->findOneBy(['bar_id' => 2]);
        $this->assertEquals($object->bar->id, 2);
    }

    public function testFindByObjectCriterion()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $bar = new Bar();
        $bar->id = 2;

        $collection = $repository->findBy(['bar' => $bar]);
        $this->assertNotEmpty($collection);
        foreach ($collection as $dummy) {
            $this->assertInstanceOf(DummyHash::class, $dummy);
            $this->assertEquals(2, $dummy->bar->id);
        }
    }

    public function testFindOneByObjectCriterion()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $bar = new Bar();
        $bar->id = 2;

        $object = $repository->findOneBy(['bar' => $bar]);
        $this->assertInstanceOf(DummyHash::class, $object);
        $this->assertEquals(2, $object->bar->id);
    }

    public function testCountByObjectCriterion()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $bar = new Bar();
        $bar->id = 2;

        $this->assertSame(
            $repository->count(['bar_id' => 2]),
            $repository->count(['bar' => $bar]),
        );
    }

    public function testFindByObjectCriterionWithNullIdThrows()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $bar = new Bar();

        $this->expectException(BadIdentifierConfigurationException::class);
        $repository->findBy(['bar' => $bar]);
    }

    public function testFindByObjectWithoutIdAttributeThrows()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);

        $this->expectException(BadIdentifierConfigurationException::class);
        $repository->findBy(['bar' => new \stdClass()]);
    }
}
