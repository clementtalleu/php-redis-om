<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\QueryBuilder;
use Talleu\RedisOm\Om\RedisFormat;

final class QueryBuilderTest extends TestCase
{
    public function testExecuteReturnsConvertedObjects(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())
            ->method('customSearch')
            ->with('prefix', '@name:{John}', RedisFormat::HASH->value)
            ->willReturn([
                ['id' => '1', 'name' => 'John'],
                ['id' => '2', 'name' => 'John'],
            ]);

        $converter = new HashObjectConverter();

        $qb = new QueryBuilder(
            redisClient: $redisClient,
            converter: $converter,
            className: QBTestEntity::class,
            redisKey: 'prefix',
            format: RedisFormat::HASH->value
        );

        $qb->query('@name:{John}');
        $results = $qb->execute();

        $this->assertCount(2, $results);
        $this->assertInstanceOf(QBTestEntity::class, $results[0]);
        $this->assertSame('John', $results[0]->name);
    }

    public function testExecuteReturnsEmptyArrayWhenNoResults(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())
            ->method('customSearch')
            ->willReturn([]);

        $converter = new HashObjectConverter();

        $qb = new QueryBuilder(
            redisClient: $redisClient,
            converter: $converter,
            className: QBTestEntity::class,
            redisKey: 'prefix',
            format: RedisFormat::HASH->value
        );

        $qb->query('@name:{NoMatch}');
        $results = $qb->execute();

        $this->assertEmpty($results);
    }
}

#[RedisOm\Entity]
class QBTestEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $name = '';
}
