<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\RedisObjectManager;

final class MergeTest extends TestCase
{
    public function testMergeOnlyUpdatesChangedFieldsHash(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->method('hGetAll')->willReturn(['id' => '1', 'name' => 'Original', 'age' => '25']);
        $redisClient->method('multi');
        $redisClient->method('exec');

        // Should call hSet for only the changed field, not hMSet for full object
        $redisClient->expects($this->once())
            ->method('hSet')
            ->with($this->anything(), 'name', 'Updated');
        $redisClient->expects($this->never())
            ->method('hMSet');

        $om = new RedisObjectManager($redisClient);

        // Load object (creates snapshot)
        /** @var MergeTestHashEntity $object */
        $object = $om->find(MergeTestHashEntity::class, 1);
        $this->assertSame('Original', $object->name);

        // Modify only name
        $object->name = 'Updated';

        // Merge instead of persist
        $om->merge($object);
        $om->flush();
    }

    public function testMergeOnlyUpdatesChangedFieldsJson(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->method('jsonGet')->willReturn('{"id":"1","name":"Original","age":"25"}');
        $redisClient->method('multi');
        $redisClient->method('exec');

        $redisClient->expects($this->once())
            ->method('jsonSetProperty')
            ->with($this->anything(), 'name', '"Updated"');
        $redisClient->expects($this->never())
            ->method('jsonSet');

        $om = new RedisObjectManager($redisClient);

        /** @var MergeTestJsonEntity $object */
        $object = $om->find(MergeTestJsonEntity::class, 1);
        $object->name = 'Updated';

        $om->merge($object);
        $om->flush();
    }

    public function testMergeSkipsWhenNothingChanged(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->method('hGetAll')->willReturn(['id' => '1', 'name' => 'Same', 'age' => '25']);

        // Should not call any write methods
        $redisClient->expects($this->never())->method('hSet');
        $redisClient->expects($this->never())->method('hMSet');
        $redisClient->expects($this->never())->method('multi');

        $om = new RedisObjectManager($redisClient);
        $object = $om->find(MergeTestHashEntity::class, 1);

        // Don't change anything
        $om->merge($object);
        $om->flush();
    }

    public function testMergeFallsBackToPersistForNewObjects(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->method('multi');
        $redisClient->method('exec');

        // New object should do full persist via hMSet
        $redisClient->expects($this->once())->method('hMSet');

        $om = new RedisObjectManager($redisClient);

        $object = new MergeTestHashEntity();
        $object->id = 99;
        $object->name = 'New';
        $object->age = 30;

        $om->merge($object);
        $om->flush();
    }

    public function testMergeMultipleFieldsChanged(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->method('hGetAll')->willReturn(['id' => '1', 'name' => 'Old', 'age' => '20']);
        $redisClient->method('multi');
        $redisClient->method('exec');

        $redisClient->expects($this->exactly(2))->method('hSet');

        $om = new RedisObjectManager($redisClient);
        $object = $om->find(MergeTestHashEntity::class, 1);

        $object->name = 'New';
        $object->age = 99;

        $om->merge($object);
        $om->flush();
    }
}

#[RedisOm\Entity]
class MergeTestHashEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = '';

    #[RedisOm\Property(index: true)]
    public int $age = 0;
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class MergeTestJsonEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = '';

    #[RedisOm\Property(index: true)]
    public int $age = 0;
}
