<?php

declare(strict_types=1);

namespace ApiPlatform\Filters;

use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('provideTrueValues')]
    public function testBooleanTrue(string $value): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', "/api/dummies?enabled=$value");
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals(2, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertEquals(true, $result['enabled']);
        }
    }

    public static function provideTrueValues(): array
    {
        return [
            'string "true"' => ['true'],
            'numeric string "1"' => ['1'],
        ];
    }

    #[DataProvider('provideFalseValues')]
    public function testBooleanFalse(string $value): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', "/api/dummies?enabled=$value");
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals(1, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertEquals(false, $result['enabled']);
        }
    }

    public static function provideFalseValues(): array
    {
        return [
            'string "false"' => ['false'],
            'numeric string "0"' => ['0'],
        ];
    }

    #[DataProvider('provideInvalidValues')]
    public function testBooleanInvalidIgnored(string $value): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', "/api/dummies?enabled=$value");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $response->toArray()['totalItems']);
    }

    public static function provideInvalidValues(): array
    {
        return [
            'non-boolean string' => ['foo'],
        ];
    }
}
