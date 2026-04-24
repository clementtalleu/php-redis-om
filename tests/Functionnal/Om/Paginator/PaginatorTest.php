<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Paginator;

use Talleu\RedisOm\Om\Paginator;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class PaginatorTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(self::createRedisClient());
        parent::setUp();
    }

    public function testPaginateHashFirstPage(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);
        $paginator = $repo->paginate([], page: 1, itemsPerPage: 2);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertSame(3, $paginator->getTotalItems());
        $this->assertSame(1, $paginator->getCurrentPage());
        $this->assertSame(2, $paginator->getItemsPerPage());
        $this->assertCount(2, $paginator->getItems());
        $this->assertTrue($paginator->hasNextPage());
        $this->assertFalse($paginator->hasPreviousPage());
        $this->assertSame(2, $paginator->getTotalPages());
    }

    public function testPaginateHashSecondPage(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);
        $paginator = $repo->paginate([], page: 2, itemsPerPage: 2);

        $this->assertCount(1, $paginator->getItems());
        $this->assertSame(2, $paginator->getCurrentPage());
        $this->assertFalse($paginator->hasNextPage());
        $this->assertTrue($paginator->hasPreviousPage());
    }

    public function testPaginateWithCriteria(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);
        $paginator = $repo->paginate(['name' => 'Olivier'], page: 1, itemsPerPage: 10);

        $this->assertSame(2, $paginator->getTotalItems());
        $this->assertCount(2, $paginator->getItems());
        foreach ($paginator as $item) {
            $this->assertSame('Olivier', $item->name);
        }
    }

    public function testPaginateJson(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $repo = $this->objectManager->getRepository(DummyJson::class);
        $paginator = $repo->paginate([], page: 1, itemsPerPage: 2);

        $this->assertSame(3, $paginator->getTotalItems());
        $this->assertCount(2, $paginator->getItems());
        foreach ($paginator->getItems() as $item) {
            $this->assertInstanceOf(DummyJson::class, $item);
        }
    }

    public function testPaginateEmptyResult(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $repo = $this->objectManager->getRepository(DummyHash::class);
        $paginator = $repo->paginate(['name' => 'NonExistent']);

        $this->assertSame(0, $paginator->getTotalItems());
        $this->assertEmpty($paginator->getItems());
        $this->assertSame(0, $paginator->getTotalPages());
    }
}
