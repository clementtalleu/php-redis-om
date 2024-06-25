<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Mapping;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\SpecialCharsDummyHash;
use Talleu\RedisOm\Tests\Fixtures\Json\SpecialCharsDummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class SpecialCharsTest extends RedisAbstractTestCase
{
    public function testSpecialCharsFindOneBy()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyHash::class);
        $object = $repository->findOneBy(['specialChars' => 'ok:']);
        $this->assertEquals($dummies[1], $object);
    }

    public function testSpecialCharsFindOneByJson()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(SpecialCharsDummyJson::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyJson::class);
        $object = $repository->findOneBy(['specialChars' => 'ok:']);
        $this->assertEquals($dummies[1], $object);
    }

    public function testSpecialCharsFindOneByBadSearch()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyHash::class);
        $object = $repository->findOneBy(['specialChars' => 'ok::']);
        $this->assertNull($object);
    }

    public function testSpecialCharsFindOneByBadSearchJson()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyJson::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyJson::class);
        $object = $repository->findOneBy(['specialChars' => 'ok::']);
        $this->assertNull($object);
    }

    public function testSpecialCharsFindByBadSearch()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyHash::class);
        $collection = $repository->findBy(['specialChars' => 'ok::']);
        $this->assertEmpty($collection);
    }

    public function testSpecialCharsFindByBadSearchJson()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyJson::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyJson::class);
        $collection = $repository->findBy(['specialChars' => 'ok::']);
        $this->assertEmpty($collection);
    }

    public function testSpecialCharsFindBy()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyHash::class);
        $collection = $repository->findBy(['specialChars' => 'ok:']);
        $this->assertEquals($dummies[1], $collection[0]);
        $this->assertEquals($dummies[2], $collection[1]);
        $this->assertEquals($dummies[0], $collection[2]);
    }

    public function testSpecialCharsFindByJson()
    {
        static::emptyRedis();
        static::generateIndex();
        $dummies = static::loadRedisFixtures(SpecialCharsDummyJson::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyJson::class);
        $collection = $repository->findBy(['specialChars' => 'ok:']);
        $this->assertEquals($dummies[1], $collection[0]);
        $this->assertEquals($dummies[2], $collection[1]);
        $this->assertEquals($dummies[0], $collection[2]);
    }

    public function testOtherSpecialCharsFindOneBy()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyHash::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyHash::class);
        $object = $repository->findOneBy(['specialChars' => 'o//\\']);
        $this->assertNull($object);
    }

    public function testOtherSpecialCharsFindOneByJson()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(SpecialCharsDummyJson::class);

        $repository = (new RedisObjectManager())->getRepository(SpecialCharsDummyJson::class);
        $object = $repository->findOneBy(['specialChars' => 'o//\\']);
        $this->assertNull($object);
    }
}
