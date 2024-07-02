<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHashWithSpaceChars;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class HashSpaceCharsRepositoryTest extends RedisAbstractTestCase
{
    public function testFindOneBySpace()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyHashWithSpaceChars::class);

        $objectManager = new RedisObjectManager();
        $repository = $objectManager->getRepository(DummyHashWithSpaceChars::class);

        // Update 1 object to set unknown to not null
        /** @var DummyHashWithSpaceChars $object */
        $object = $repository->findOneBy(['spaceChars' => 'With space']);
        $this->assertEquals($object->spaceChars, 'With space');
    }

    public function testFindBySpace()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyHashWithSpaceChars::class);

        $objectManager = new RedisObjectManager();
        $repository = $objectManager->getRepository(DummyHashWithSpaceChars::class);

        // Update 1 object to set unknown to not null
        /** @var DummyHashWithSpaceChars[] $collection */
        $collection = $repository->findBy(['spaceChars' => 'With space']);
        foreach ($collection as $object){
            $this->assertEquals($object->spaceChars, 'With space');
        }
    }
}
