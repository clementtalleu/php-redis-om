<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\ApiPlatform\Operations;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class GetCollectionTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testGetCollection(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies');

        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals(3, $responseContent['totalItems']);
        $this->assertCount(3, $responseContent['member']);
        $this->assertArrayHasKey('@id', $responseContent['member'][0]);
        $this->assertArrayHasKey('@id', $responseContent['member'][0]);
        $this->assertArrayHasKey('id', $responseContent['member'][0]);
        $this->assertArrayHasKey('name', $responseContent['member'][0]);
        $this->assertArrayHasKey('createdAt', $responseContent['member'][0]);
        $this->assertArrayHasKey('createdAtImmutable', $responseContent['member'][0]);
        $this->assertArrayHasKey('age', $responseContent['member'][0]);
        $this->assertArrayHasKey('bar', $responseContent['member'][0]);
        $this->assertIsArray($responseContent['member'][0]['bar']);
    }
}
