<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Repository;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\RedisObjectManager;

final class FindMultipleTest extends TestCase
{
    public function testFindMultipleHash(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())
            ->method('hGetAllMultiple')
            ->willReturn([
                'key:1' => ['id' => '1', 'name' => 'First'],
                'key:3' => ['id' => '3', 'name' => 'Third'],
            ]);

        $om = new RedisObjectManager($redisClient);
        $repo = $om->getRepository(FindMultipleHashEntity::class);
        $results = $repo->findMultiple([1, 2, 3]);

        $this->assertCount(2, $results);
        $this->assertSame(1, $results[0]->id);
        $this->assertSame('First', $results[0]->name);
        $this->assertSame(3, $results[1]->id);
    }

    public function testFindMultipleJson(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())
            ->method('jsonGetMultiple')
            ->willReturn([
                'key:1' => '{"id":"1","name":"First"}',
                'key:2' => null,
                'key:3' => '{"id":"3","name":"Third"}',
            ]);

        $om = new RedisObjectManager($redisClient);
        $repo = $om->getRepository(FindMultipleJsonEntity::class);
        $results = $repo->findMultiple([1, 2, 3]);

        $this->assertCount(2, $results);
        $this->assertSame(1, $results[0]->id);
        $this->assertSame(3, $results[1]->id);
    }

    public function testFindMultipleEmpty(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())
            ->method('hGetAllMultiple')
            ->with([])
            ->willReturn([]);

        $om = new RedisObjectManager($redisClient);
        $repo = $om->getRepository(FindMultipleHashEntity::class);
        $results = $repo->findMultiple([]);

        $this->assertEmpty($results);
    }
}

#[RedisOm\Entity]
class FindMultipleHashEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $name = '';
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class FindMultipleJsonEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $name = '';
}
