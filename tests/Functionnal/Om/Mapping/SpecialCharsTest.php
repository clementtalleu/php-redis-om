<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Mapping;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\SpecialCharsDummyHash;
use Talleu\RedisOm\Tests\Fixtures\Hash\UuidStringDummyHash;
use Talleu\RedisOm\Tests\Fixtures\Json\SpecialCharsDummyJson;
use Talleu\RedisOm\Tests\Fixtures\Json\UuidStringDummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class SpecialCharsTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testSpecialCharsFindOneBy()
    {
        static::emptyRedis();
        static::generateIndex();
        /** @var SpecialCharsDummyHash[] $dummies */
        $dummies = static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = $this->objectManager->getRepository(SpecialCharsDummyHash::class);
        $object = $repository->findOneBy(['specialChars' => 'ok:']);
        $this->assertEquals($dummies[0], $object);
    }

    public function testSpecialCharsFindOneByJson()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(SpecialCharsDummyJson::class);

        $repository = $this->objectManager->getRepository(SpecialCharsDummyJson::class);
        $object = $repository->findOneBy(['specialChars' => 'ok:']);
        $this->assertEquals($dummies[0], $object);
    }

    public function testSpecialCharsFindOneByBadSearch()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = $this->objectManager->getRepository(SpecialCharsDummyHash::class);
        $object = $repository->findOneBy(['specialChars' => 'ok::']);
        $this->assertNull($object);
    }

    public function testSpecialCharsFindOneByBadSearchJson()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyJson::class);

        $repository = $this->objectManager->getRepository(SpecialCharsDummyJson::class);
        $object = $repository->findOneBy(['specialChars' => 'ok::']);
        $this->assertNull($object);
    }

    public function testSpecialCharsFindByBadSearch()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = $this->objectManager->getRepository(SpecialCharsDummyHash::class);
        $collection = $repository->findBy(['specialChars' => 'ok::']);
        $this->assertEmpty($collection);
    }

    public function testSpecialCharsFindByBadSearchJson()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyJson::class);

        $repository = $this->objectManager->getRepository(SpecialCharsDummyJson::class);
        $collection = $repository->findBy(['specialChars' => 'ok::']);
        $this->assertEmpty($collection);
    }

    public function testSpecialCharsFindBy()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = $this->objectManager->getRepository(SpecialCharsDummyHash::class);
        $collection = $repository->findBy(['specialChars' => 'ok:']);

        $this->assertEquals($dummies[0], $collection[0]);
        $this->assertEquals($dummies[1], $collection[1]);
        $this->assertEquals($dummies[2], $collection[2]);
    }

    public function testSpecialCharsFindByJson()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(SpecialCharsDummyJson::class);
        $repository = $this->objectManager->getRepository(SpecialCharsDummyJson::class);
        $collection = $repository->findBy(['specialChars' => 'ok:']);

        $this->assertEquals($dummies[0], $collection[0]);
        $this->assertEquals($dummies[1], $collection[1]);
        $this->assertEquals($dummies[2], $collection[2]);
    }

    public function testOtherSpecialCharsFindOneBy()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = $this->objectManager->getRepository(SpecialCharsDummyHash::class);
        $object = $repository->findOneBy(['specialChars' => 'o//\\']);
        $this->assertNull($object);
    }

    public function testOtherSpecialCharsFindOneByJson()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyJson::class);

        $repository = $this->objectManager->getRepository(SpecialCharsDummyJson::class);
        $object = $repository->findOneBy(['specialChars' => 'o//\\']);
        $this->assertNull($object);
    }

    public function testUuidSpecialCharsFindOneBy(): void
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(UuidStringDummyHash::class);

        $repository = $this->objectManager->getRepository(UuidStringDummyHash::class);
        $object = $repository->findOneBy(['specialChars' => '1f0f2176-5dae-6524-8d77-dbe79e5a7b83']);
        $this->assertEquals($dummies[0], $object);
    }

    public function testUuidSpecialCharsFindOneByJson(): void
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(UuidStringDummyJson::class);

        $repository = $this->objectManager->getRepository(UuidStringDummyJson::class);
        $object = $repository->findOneBy(['specialChars' => '1f0f2176-5dae-6524-8d77-dbe79e5a7b83']);
        $this->assertEquals($dummies[0], $object);
    }
}
