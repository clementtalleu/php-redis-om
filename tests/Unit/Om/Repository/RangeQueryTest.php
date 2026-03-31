<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Repository;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\RedisObjectManager;

final class RangeQueryTest extends TestCase
{
    private RedisClientInterface $redisClient;
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->redisClient = $this->createMock(RedisClientInterface::class);
        $this->objectManager = new RedisObjectManager($this->redisClient);
    }

    public function testFindByWithGteAndLte(): void
    {
        $this->redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->equalTo([]),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(['age' => '@age_numeric:[18 65]']),
            )
            ->willReturn([]);

        $repo = $this->objectManager->getRepository(RangeTestEntity::class);
        $repo->findBy(['age' => ['$gte' => 18, '$lte' => 65]]);
    }

    public function testFindByWithGtOnly(): void
    {
        $this->redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->equalTo([]),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(['price' => '@price_numeric:[(10 +inf]']),
            )
            ->willReturn([]);

        $repo = $this->objectManager->getRepository(RangeTestEntity::class);
        $repo->findBy(['price' => ['$gt' => 10]]);
    }

    public function testFindByWithLtOnly(): void
    {
        $this->redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->equalTo([]),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(['age' => '@age_numeric:[-inf (100]']),
            )
            ->willReturn([]);

        $repo = $this->objectManager->getRepository(RangeTestEntity::class);
        $repo->findBy(['age' => ['$lt' => 100]]);
    }

    public function testFindByMixesRangeAndExactCriteria(): void
    {
        $this->redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->equalTo(['name' => 'John']),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(['age' => '@age_numeric:[18 +inf]']),
            )
            ->willReturn([]);

        $repo = $this->objectManager->getRepository(RangeTestEntity::class);
        $repo->findBy(['name' => 'John', 'age' => ['$gte' => 18]]);
    }

    public function testFindOneByWithRange(): void
    {
        $this->redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->equalTo([]),
                $this->anything(),
                $this->anything(),
                $this->equalTo(1),
                $this->anything(),
                $this->anything(),
                $this->equalTo(['age' => '@age_numeric:[0 17]']),
            )
            ->willReturn([]);

        $repo = $this->objectManager->getRepository(RangeTestEntity::class);
        $repo->findOneBy(['age' => ['$gte' => 0, '$lte' => 17]]);
    }

    public function testNonRangeArrayCriteriaPassedThrough(): void
    {
        // Arrays without $gte/$lte/$gt/$lt should not be treated as ranges
        $this->redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->equalTo(['tags' => ['php', 'redis']]),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo([]),
            )
            ->willReturn([]);

        $repo = $this->objectManager->getRepository(RangeTestEntity::class);
        $repo->findBy(['tags' => ['php', 'redis']]);
    }
}

#[RedisOm\Entity]
class RangeTestEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = '';

    #[RedisOm\Property(index: true)]
    public int $age = 0;

    #[RedisOm\Property(index: true)]
    public float $price = 0.0;

    #[RedisOm\Property]
    public array $tags = [];
}
