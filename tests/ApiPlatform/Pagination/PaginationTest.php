<?php

declare(strict_types=1);

namespace ApiPlatform\Pagination;

use Talleu\RedisOm\Tests\ApiPlatform\Entity\Book;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class PaginationTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testPaginationTotalItems(): void
    {
        self::emptyRedis();
        self::generateIndex();
        static::loadRedisFixtures(Book::class);

        $responseContent = self::createClient()->request('GET', '/api/books')->toArray();

        $this->assertEquals(154, $responseContent['totalItems']);
        $this->assertCount(30, $responseContent['member']);
        $this->assertEquals('Nom_1', $responseContent['member'][0]['name']);

        $responseContent = self::createClient()->request('GET', '/api/books?page=2')->toArray();

        $this->assertEquals(154, $responseContent['totalItems']);
        $this->assertCount(30, $responseContent['member']);
        $this->assertEquals('Nom_31', $responseContent['member'][0]['name']);

        $lastPage = $responseContent['view']['last'];
        $responseContent = self::createClient()->request('GET', $lastPage)->toArray();
        $this->assertCount(4, $responseContent['member']);
    }

    public function testNoPagination(): void
    {
        self::emptyRedis();
        self::generateIndex();
        static::loadRedisFixtures(Book::class);

        $responseContent = self::createClient()->request('GET', '/api/books?pagination=false')->toArray();
        $this->assertEquals(154, $responseContent['totalItems']);
        $this->assertCount(154, $responseContent['member']);
    }

    public function testItemsPerPage(): void
    {
        self::emptyRedis();
        self::generateIndex();
        static::loadRedisFixtures(Book::class);

        $responseContent = self::createClient()->request('GET', '/api/books?itemsPerPage=5')->toArray();
        $this->assertEquals(154, $responseContent['totalItems']);
        $this->assertCount(5, $responseContent['member']);
    }

    public static function loadRedisFixtures(?string $dummyClass = DummyHash::class, ?bool $flush = true): array
    {
        $objectManager = new RedisObjectManager(self::createRedisClient());
        for ($i = 1; $i < 155; $i++) {
            $book = new Book();
            $book->name = 'Nom_'.$i;
            $objectManager->persist($book);
        }

        $objectManager->flush();
        return [];
    }
}
