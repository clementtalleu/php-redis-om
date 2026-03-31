<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\Paginator;
use Talleu\RedisOm\Om\RedisObjectManager;

final class PaginatorTest extends TestCase
{
    public function testPaginatorBasics(): void
    {
        $items = [new \stdClass(), new \stdClass()];
        $paginator = new Paginator($items, 50, 1, 10);

        $this->assertSame(50, $paginator->getTotalItems());
        $this->assertSame(1, $paginator->getCurrentPage());
        $this->assertSame(10, $paginator->getItemsPerPage());
        $this->assertSame(5, $paginator->getTotalPages());
        $this->assertCount(2, $paginator->getItems());
    }

    public function testHasNextPage(): void
    {
        $paginator = new Paginator([], 50, 1, 10);
        $this->assertTrue($paginator->hasNextPage());

        $paginator = new Paginator([], 50, 5, 10);
        $this->assertFalse($paginator->hasNextPage());
    }

    public function testHasPreviousPage(): void
    {
        $paginator = new Paginator([], 50, 1, 10);
        $this->assertFalse($paginator->hasPreviousPage());

        $paginator = new Paginator([], 50, 3, 10);
        $this->assertTrue($paginator->hasPreviousPage());
    }

    public function testTotalPagesRoundsUp(): void
    {
        $paginator = new Paginator([], 51, 1, 10);
        $this->assertSame(6, $paginator->getTotalPages());

        $paginator = new Paginator([], 50, 1, 10);
        $this->assertSame(5, $paginator->getTotalPages());

        $paginator = new Paginator([], 1, 1, 10);
        $this->assertSame(1, $paginator->getTotalPages());
    }

    public function testEmptyPaginator(): void
    {
        $paginator = new Paginator([], 0, 1, 10);

        $this->assertSame(0, $paginator->getTotalItems());
        $this->assertSame(0, $paginator->getTotalPages());
        $this->assertFalse($paginator->hasNextPage());
        $this->assertFalse($paginator->hasPreviousPage());
        $this->assertCount(0, $paginator);
    }

    public function testCountable(): void
    {
        $items = [new \stdClass(), new \stdClass(), new \stdClass()];
        $paginator = new Paginator($items, 100, 1, 10);

        $this->assertCount(3, $paginator);
    }

    public function testIterable(): void
    {
        $items = [new \stdClass(), new \stdClass()];
        $paginator = new Paginator($items, 100, 1, 10);

        $iterated = [];
        foreach ($paginator as $item) {
            $iterated[] = $item;
        }

        $this->assertCount(2, $iterated);
    }

    public function testRepositoryPaginate(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())
            ->method('count')
            ->willReturn(25);
        $redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(10),
                $this->equalTo(10),  // offset = (page-1) * perPage = (2-1)*10 = 10
            )
            ->willReturn([
                ['id' => '11', 'name' => 'Page2-1'],
                ['id' => '12', 'name' => 'Page2-2'],
            ]);

        $om = new RedisObjectManager($redisClient);
        $repo = $om->getRepository(PaginatorTestEntity::class);

        $paginator = $repo->paginate([], page: 2, itemsPerPage: 10);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertSame(25, $paginator->getTotalItems());
        $this->assertSame(2, $paginator->getCurrentPage());
        $this->assertSame(10, $paginator->getItemsPerPage());
        $this->assertSame(3, $paginator->getTotalPages());
        $this->assertTrue($paginator->hasNextPage());
        $this->assertTrue($paginator->hasPreviousPage());
        $this->assertCount(2, $paginator->getItems());
    }

    public function testRepositoryPaginatePageOneNoOffset(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->method('count')->willReturn(5);
        $redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(20),
                $this->equalTo(0),  // offset = 0 for page 1
            )
            ->willReturn([]);

        $om = new RedisObjectManager($redisClient);
        $repo = $om->getRepository(PaginatorTestEntity::class);
        $repo->paginate([], page: 1, itemsPerPage: 20);
    }
}

#[RedisOm\Entity]
class PaginatorTestEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = '';
}
