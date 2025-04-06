<?php

declare(strict_types=1);

namespace ApiPlatform\Operations;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class GetItemTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testGet(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies/1');

        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertArrayHasKey('@id', $responseContent);
        $this->assertArrayHasKey('@id', $responseContent);
        $this->assertArrayHasKey('id', $responseContent);
        $this->assertArrayHasKey('name', $responseContent);
        $this->assertArrayHasKey('createdAt', $responseContent);
        $this->assertArrayHasKey('createdAtImmutable', $responseContent);
        $this->assertArrayHasKey('age', $responseContent);
        $this->assertArrayHasKey('bar', $responseContent);
        $this->assertIsArray($responseContent['bar']);
    }
}
