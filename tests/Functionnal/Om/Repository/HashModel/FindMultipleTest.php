<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class FindMultipleTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(self::createRedisClient());
        parent::setUp();
    }

    public function testFindMultiple(): void
    {
        static::emptyRedis();
        static::generateIndex();
        $fixtures = static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);
        $results = $repo->findMultiple([1, 3]);

        $this->assertCount(2, $results);
        $ids = array_map(fn ($r) => $r->id, $results);
        $this->assertContains(1, $ids);
        $this->assertContains(3, $ids);
    }

    public function testFindMultipleWithMissingIds(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);
        $results = $repo->findMultiple([1, 999, 3]);

        // 999 doesn't exist, should return only 2 results
        $this->assertCount(2, $results);
    }

    public function testFindMultipleEmpty(): void
    {
        static::emptyRedis();
        static::generateIndex();

        $repo = $this->objectManager->getRepository(DummyHash::class);
        $results = $repo->findMultiple([]);

        $this->assertEmpty($results);
    }
}
