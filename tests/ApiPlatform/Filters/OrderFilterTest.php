<?php

declare(strict_types=1);

namespace ApiPlatform\Filters;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class OrderFilterTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }
    //
    // public function testOrderAsc(): void
    // {
    //     self::emptyRedis();
    //     self::generateIndex();
    //     self::loadRedisFixtures(Dummy::class);
    //
    //     $response = self::createClient()->request('GET', '/api/dummies?order[id]=ASC');
    //     $this->assertEquals(200, $response->getStatusCode());
    //     $responseContent = $response->toArray();
    //     $this->assertEquals(3, $responseContent['totalItems']);
    //     $this->assertTrue($responseContent['member'][0]['id'] < $responseContent['member'][1]['id']);
    //     $this->assertTrue($responseContent['member'][1]['id'] < $responseContent['member'][2]['id']);
    // }
    //
    // public function testOrderDesc(): void
    // {
    //     self::emptyRedis();
    //     self::generateIndex();
    //     self::loadRedisFixtures(Dummy::class);
    //
    //     $response = self::createClient()->request('GET', '/api/dummies?order[id]=DESC');
    //     $this->assertEquals(200, $response->getStatusCode());
    //     $responseContent = $response->toArray();
    //     $this->assertEquals(3, $responseContent['totalItems']);
    //     $this->assertTrue($responseContent['member'][0]['id'] > $responseContent['member'][1]['id']);
    //     $this->assertTrue($responseContent['member'][1]['id'] > $responseContent['member'][2]['id']);
    // }

    public function testOrderAscWithSearch(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?order[age]=ASC&name=Olivier');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(2, $responseContent['totalItems']);
        $this->assertTrue($responseContent['member'][0]['age'] < $responseContent['member'][1]['age']);
        $this->assertEquals('Olivier', $responseContent['member'][0]['name']);
        $this->assertEquals('Olivier', $responseContent['member'][1]['name']);
    }

    public function testOrderDescWithSearch(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?order[age]=DESC&name=Olivier');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray(false);

        $this->assertEquals(2, $responseContent['totalItems']);
        $this->assertTrue($responseContent['member'][0]['age'] > $responseContent['member'][1]['age']);
        $this->assertEquals('Olivier', $responseContent['member'][0]['name']);
        $this->assertEquals('Olivier', $responseContent['member'][1]['name']);
    }
}
