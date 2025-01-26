<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\JsonModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJsonWithSpaceChars;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class JsonSpaceCharsRepositoryTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createClient());
        parent::setUp();
    }

    public function testFindOneBySpace()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJsonWithSpaceChars::class);

        $repository = $this->objectManager->getRepository(DummyJsonWithSpaceChars::class);

        // Update 1 object to set unknown to not null
        /** @var DummyJsonWithSpaceChars $object */
        $object = $repository->findOneBy(['spaceChars' => 'With space']);
        $this->assertEquals($object->spaceChars, 'With space');
    }

    public function testFindBySpace()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJsonWithSpaceChars::class);

        $repository = $this->objectManager->getRepository(DummyJsonWithSpaceChars::class);

        // Update 1 object to set unknown to not null
        /** @var DummyJsonWithSpaceChars[] $collection */
        $collection = $repository->findBy(['spaceChars' => 'With space']);
        foreach ($collection as $object) {
            $this->assertEquals($object->spaceChars, 'With space');
        }
    }
}
