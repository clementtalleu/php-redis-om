<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Repository;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisObjectManager;

final class GeoQueryTest extends TestCase
{
    public function testFindByGeoRadius(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->equalTo([]),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(['_geo' => '@location:[2.3522 48.8566 10 km]']),
            )
            ->willReturn([
                ['id' => '1', 'name' => 'Paris Place', 'location' => '2.35,48.85'],
            ]);

        $om = new RedisObjectManager($redisClient);
        $repo = $om->getRepository(GeoTestEntity::class);

        $results = $repo->findByGeoRadius('location', 2.3522, 48.8566, 10, 'km');

        $this->assertCount(1, $results);
        $this->assertInstanceOf(GeoTestEntity::class, $results[0]);
    }

    public function testFindByGeoRadiusWithMiles(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())
            ->method('search')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(['_geo' => '@location:[-73.9857 40.7484 5 mi]']),
            )
            ->willReturn([]);

        $om = new RedisObjectManager($redisClient);
        $repo = $om->getRepository(GeoTestEntity::class);

        $results = $repo->findByGeoRadius('location', -73.9857, 40.7484, 5, 'mi');
        $this->assertEmpty($results);
    }
}

#[RedisOm\Entity]
class GeoTestEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = '';

    #[RedisOm\Property(index: ['location' => 'GEO'])]
    public string $location = '';
}
