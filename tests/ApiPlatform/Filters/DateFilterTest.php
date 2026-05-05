<?php

declare(strict_types=1);

namespace ApiPlatform\Filters;

use PHPUnit\Framework\Attributes\DataProvider;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class DateFilterTest extends RedisAbstractTestCase
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
            'exact match' => ['createdAt=2022-01-01', 1],
            'exact no match' => ['createdAt=2000-01-01', 0],
            'after' => ['createdAt[after]=2015-01-01', 2],
            'after no match' => ['createdAt[after]=2023-01-01', 0],
            'before' => ['createdAt[before]=2020-01-01', 2],
            'before no match' => ['createdAt[before]=2010-01-01', 0],
            'strictly_after' => ['createdAt[strictly_after]=2018-05-01', 1],
            'strictly_before' => ['createdAt[strictly_before]=2018-05-01', 1],
            'range after+before' => ['createdAt[after]=2015-01-01&createdAt[before]=2020-01-01', 1],
        ];
    }
}
