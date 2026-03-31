<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\JsonModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
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
        static::loadRedisFixtures(DummyJson::class);

        $repo = $this->objectManager->getRepository(DummyJson::class);
        $results = $repo->findMultiple([1, 2, 3]);

        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(DummyJson::class, $result);
        }
    }

    public function testFindMultipleWithMissingIds(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repo = $this->objectManager->getRepository(DummyJson::class);
        $results = $repo->findMultiple([1, 999]);

        $this->assertCount(1, $results);
    }
}
