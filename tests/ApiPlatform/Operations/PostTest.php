<?php

declare(strict_types=1);

namespace ApiPlatform\Operations;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class PostTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testPost(): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('POST', '/api/dummies', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Emile',
                'age' => 7,
                'partialName' => 'Mimile',
                'enabled' => false,
                'createdAt' => '2018-11-20',
                'complexData' => [
                    'foo' => 'bar'
                ]
            ],
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $responseContent = $response->toArray();

        $this->assertArrayHasKey('@id', $responseContent);
        $this->assertArrayHasKey('@id', $responseContent);
        $this->assertArrayHasKey('id', $responseContent);
        $this->assertArrayHasKey('name', $responseContent);
        $this->assertArrayHasKey('createdAt', $responseContent);
        $this->assertArrayHasKey('age', $responseContent);
        $this->assertIsArray($responseContent['complexData']);

        // Check values
        $this->assertEquals('Emile', $responseContent['name']);
        $this->assertEquals('Mimile', $responseContent['partialName']);
        $this->assertEquals('2018-11-20T00:00:00+00:00', $responseContent['createdAt']);
        $this->assertEquals(['foo' => 'bar'], $responseContent['complexData']);
    }
}
