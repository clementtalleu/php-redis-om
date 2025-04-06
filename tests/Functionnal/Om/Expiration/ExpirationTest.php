<?php

declare(strict_types=1);

namespace Functionnal\Om\Expiration;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\ExpirationHash;
use Talleu\RedisOm\Tests\Fixtures\Json\ExpirationJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class ExpirationTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testExpiration()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(ExpirationHash::class);

        $repository = $this->objectManager->getRepository(ExpirationHash::class);
        $this->assertEquals(3, count(iterator_to_array($repository->findAll())));

        // Wait 3 seconds, the objects must have disappeared
        sleep(3);

        $this->assertEquals(0, count(iterator_to_array($repository->findAll())));
    }

    public function testExpirationJson()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(ExpirationJson::class);

        $repository = $this->objectManager->getRepository(ExpirationJson::class);
        $this->assertEquals(3, count(iterator_to_array($repository->findAll())));

        // Wait 3 seconds, the objects must have disappeared
        sleep(3);

        $this->assertEquals(0, count(iterator_to_array($repository->findAll())));
    }
}
