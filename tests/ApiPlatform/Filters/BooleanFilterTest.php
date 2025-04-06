<?php

declare(strict_types=1);

namespace ApiPlatform\Filters;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class BooleanFilterTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testBooleanTrue(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?enabled=true');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals(2, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertEquals(true, $result['enabled']);
        }
    }

    public function testBooleanFalse(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?enabled=false');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals(1, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertEquals(false, $result['enabled']);
        }
    }
}
