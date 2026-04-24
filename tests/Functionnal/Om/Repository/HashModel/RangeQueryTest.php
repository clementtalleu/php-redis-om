<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class RangeQueryTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(self::createRedisClient());
        parent::setUp();
    }

    public function testFindByGteAndLte(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);

        // Fixtures: ages 20, 18, 34
        $results = $repo->findBy(['age' => ['$gte' => 19, '$lte' => 34]]);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(19, $result->age);
            $this->assertLessThanOrEqual(34, $result->age);
        }
    }

    public function testFindByGtOnly(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);

        // age > 20 → should return age=34 only (gt is exclusive)
        $results = $repo->findBy(['age' => ['$gt' => 20]]);

        $this->assertCount(1, $results);
        $this->assertSame(34, $results[0]->age);
    }

    public function testFindByLtOnly(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);

        // age < 20 → should return age=18 only
        $results = $repo->findBy(['age' => ['$lt' => 20]]);

        $this->assertCount(1, $results);
        $this->assertSame(18, $results[0]->age);
    }

    public function testFindByRangeWithOtherCriteria(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);

        // name=Olivier AND age >= 20
        $results = $repo->findBy(['name' => 'Olivier', 'age' => ['$gte' => 20]]);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertSame('Olivier', $result->name);
            $this->assertGreaterThanOrEqual(20, $result->age);
        }
    }

    public function testFindByRangeNoResults(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);

        $results = $repo->findBy(['age' => ['$gte' => 100]]);
        $this->assertEmpty($results);
    }
}
