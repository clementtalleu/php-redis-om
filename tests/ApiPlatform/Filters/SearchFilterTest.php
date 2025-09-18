<?php

declare(strict_types=1);

namespace ApiPlatform\Filters;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class SearchFilterTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testSearchExact(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?name=Olivier');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(2, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertEquals('Olivier', $result['name']);
        }
    }

    public function testSearchExactEmpty(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?name=Test');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(0, $responseContent['totalItems']);
    }

    public function testSearchPartial(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?partialName=ar');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(3, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertEquals('Martin', $result['partialName']);
        }
    }

    public function testSearchPartialEmpty(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?partialName=Test');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(0, $responseContent['totalItems']);
    }

    public function testSearchStartWith(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?startWithName=Mar');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(3, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertEquals('Martin', $result['startWithName']);
        }
    }

    public function testSearchStartWithEmpty(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?startWithName=Test');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(0, $responseContent['totalItems']);
    }

    public function testSearchEndWith(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?endWithName=in');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(3, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertEquals('Martin', $result['startWithName']);
        }
    }

    public function testSearchEndWithEmpty(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', '/api/dummies?endWithName=Test');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertEquals(0, $responseContent['totalItems']);
    }
}
