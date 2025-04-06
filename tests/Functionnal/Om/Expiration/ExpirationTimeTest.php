<?php

declare(strict_types=1);

namespace Functionnal\Om\Expiration;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\Fixtures\Hash\ExpirationTimeHash;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
use Talleu\RedisOm\Tests\Fixtures\Json\ExpirationJson;
use Talleu\RedisOm\Tests\Fixtures\Json\ExpirationTimeJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class ExpirationTimeTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testExpiration()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(ExpirationTimeHash::class);

        $repository = $this->objectManager->getRepository(ExpirationTimeHash::class);
        $firstElement = $repository->findOneBy([]);

        $nextHour = (new \DateTimeImmutable())->modify('+1 hour');
        $expirationTime = $this->objectManager->getExpirationTime($firstElement);
        $this->assertInstanceOf(\DateTimeImmutable::class, $expirationTime);
        $this->assertGreaterThan($nextHour, $expirationTime);
    }

    public function testExpirationNoTTL()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repository = $this->objectManager->getRepository(DummyHash::class);
        $firstElement = $repository->findOneBy([]);
        $expirationTime = $this->objectManager->getExpirationTime($firstElement);
        $this->assertNull($expirationTime);
    }

    public function testExpirationJson()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(ExpirationTimeJson::class);

        $repository = $this->objectManager->getRepository(ExpirationTimeJson::class);
        $firstElement = $repository->findOneBy([]);

        $nextHour = (new \DateTimeImmutable())->modify('+1 hour');
        $expirationTime = $this->objectManager->getExpirationTime($firstElement);
        $this->assertInstanceOf(\DateTimeImmutable::class, $expirationTime);
        $this->assertGreaterThan($nextHour, $expirationTime);
    }

    public function testExpirationJsonNoTTL()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repository = $this->objectManager->getRepository(DummyJson::class);
        $firstElement = $repository->findOneBy([]);
        $expirationTime = $this->objectManager->getExpirationTime($firstElement);
        $this->assertNull($expirationTime);
    }
}
