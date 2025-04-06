<?php

declare(strict_types=1);

namespace ApiPlatform\Filters;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class NumericFilterTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testNumeric(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?age=20');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(1, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertEquals(20, $result['age']);
        }
    }

    public function testNumericEmpty(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?age=45');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals(0, $responseContent['totalItems']);
    }
}
