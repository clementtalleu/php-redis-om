<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Mapping;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\PrefixDummyHash;
use Talleu\RedisOm\Tests\Fixtures\Json\PrefixDummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class PrefixTest extends RedisAbstractTestCase
{
    public function testPrefix()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(PrefixDummyHash::class);


        $objectManager = new RedisObjectManager();
        $keys = $this->createClient()->keys('*');
        foreach ($keys as $key) {
            $this->assertStringContainsString('dummy:', $key);
        }

        $this->assertInstanceOf(PrefixDummyHash::class, $objectManager->find(PrefixDummyHash::class, 1));
        $this->assertInstanceOf(PrefixDummyHash::class, $objectManager->find(PrefixDummyHash::class, 2));
        $this->assertInstanceOf(PrefixDummyHash::class, $objectManager->find(PrefixDummyHash::class, 3));
    }

    public function testPrefixJson()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(PrefixDummyJson::class);


        $objectManager = new RedisObjectManager();
        $keys = $this->createClient()->keys('*');
        foreach ($keys as $key) {
            $this->assertStringContainsString('dummy:', $key);
        }

        $this->assertInstanceOf(PrefixDummyJson::class, $objectManager->find(PrefixDummyJson::class, 1));
        $this->assertInstanceOf(PrefixDummyJson::class, $objectManager->find(PrefixDummyJson::class, 2));
        $this->assertInstanceOf(PrefixDummyJson::class, $objectManager->find(PrefixDummyJson::class, 3));
    }
}
