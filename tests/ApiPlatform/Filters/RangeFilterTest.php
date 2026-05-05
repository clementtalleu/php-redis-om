<?php

declare(strict_types=1);

namespace ApiPlatform\Filters;

use PHPUnit\Framework\Attributes\DataProvider;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class RangeFilterTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    #[DataProvider('provideQuery')]
    public function testFilter(string $query, int $expectedCount): void
    {
        self::emptyRedis();
        self::generateIndex();
        self::loadRedisFixtures(Dummy::class);

        $response = self::createClient()->request('GET', "/api/dummies?$query");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedCount, $response->toArray()['totalItems']);
    }

    public static function provideQuery(): array
    {
        return [
            'gt match' => ['price[gt]=14', 2],
            'gt no match' => ['price[gt]=200', 0],
            'gte match' => ['price[gte]=14.5', 2],
            'lt match' => ['price[lt]=15', 2],
            'lte match' => ['price[lte]=14.5', 2],
            'lte no match' => ['price[lte]=5', 0],
            'between match' => ['price[between]=10..20', 2],
            'between all' => ['price[between]=0..200', 3],
            'between no match' => ['price[between]=20..50', 0],
            'combined gt+lt' => ['price[gt]=10&price[lt]=99', 2],
        ];
    }
}
