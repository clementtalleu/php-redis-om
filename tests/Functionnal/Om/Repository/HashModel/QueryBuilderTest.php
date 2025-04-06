<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class QueryBuilderTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testCustomQueryOr()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();


        $repository = $this->objectManager->getRepository(DummyHash::class);

        $queryBuilder = $repository->createQueryBuilder();
        $queryBuilder->query('@age:{20 | 34}');
        $results = $queryBuilder->execute();

        foreach ($results as $result) {
            $this->assertInstanceOf(DummyHash::class, $result);
            $this->assertContains($result->age, [20, 34]);
        }
    }

    public function testCustomQueryOrBadRequest()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();


        $repository = $this->objectManager->getRepository(DummyHash::class);

        $queryBuilder = $repository->createQueryBuilder();
        $queryBuilder->query('@age:{99 | 98}');
        $results = $queryBuilder->execute();
        $this->assertEmpty($results);
    }

    public function testCustomQueryStartWith()
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();


        $repository = $this->objectManager->getRepository(DummyHash::class);

        $queryBuilder = $repository->createQueryBuilder();
        $queryBuilder->query('@name:{Oli*}');
        $results = $queryBuilder->execute();

        foreach ($results as $result) {
            $this->assertInstanceOf(DummyHash::class, $result);
            $this->assertTrue(str_starts_with($result->name, 'Oli'));
        }
    }
}
