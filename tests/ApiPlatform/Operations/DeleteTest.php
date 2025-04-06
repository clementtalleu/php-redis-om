<?php

declare(strict_types=1);

namespace ApiPlatform\Operations;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class DeleteTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testDelete(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('DELETE', '/api/dummies/1');
        $this->assertEquals(204, $response->getStatusCode());

        // Check resource removed
        $response = self::createClient()->request('GET', '/api/dummies/1');
        $this->assertEquals(404, $response->getStatusCode());
    }
}
