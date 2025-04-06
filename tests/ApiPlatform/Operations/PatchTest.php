<?php

declare(strict_types=1);

namespace ApiPlatform\Operations;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class PatchTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testPatch(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('PATCH', '/api/dummies/1', [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'name' => 'Adèle',
                'age' => 18,
            ],
        ]);


        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals('Adèle', $responseContent['name']);
        $this->assertEquals(18, $responseContent['age']);


        $response = self::createClient()->request('GET', '/api/dummies/1');
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals('Adèle', $responseContent['name']);
        $this->assertEquals(18, $responseContent['age']);
        $this->assertEquals('Martin', $responseContent['partialName']);
    }
}
