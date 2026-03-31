<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Persister;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Persister\HashModel\HashPersister;
use Talleu\RedisOm\Om\Persister\ObjectToPersist;

final class HashPersisterTest extends TestCase
{
    public function testPersistCreatesObjectToPersist(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $persister = new HashPersister(redis: $redisClient);

        $entity = new Entity();
        $object = new PersisterTestDummy();
        $object->id = 1;
        $object->name = 'test';

        $result = $persister->persist($entity, $object);

        $this->assertInstanceOf(ObjectToPersist::class, $result);
        $this->assertSame('doPersist', $result->operation);
        $this->assertStringContainsString(':1', $result->redisKey);
    }

    public function testPersistWithTtl(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $persister = new HashPersister(redis: $redisClient);

        $entity = new Entity(ttl: 3600);
        $object = new PersisterTestDummy();
        $object->id = 1;

        $result = $persister->persist($entity, $object);

        $this->assertSame(3600, $result->ttl);
    }

    public function testDeleteCreatesDeleteOperation(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $persister = new HashPersister(redis: $redisClient);

        $entity = new Entity();
        $object = new PersisterTestDummy();
        $object->id = 1;

        $result = $persister->delete($entity, $object);

        $this->assertSame('doDelete', $result->operation);
        $this->assertStringContainsString(':1', $result->redisKey);
    }

    public function testDoPersistCallsHMSet(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())->method('hMSet');

        $persister = new HashPersister(redis: $redisClient);

        $object = new PersisterTestDummy();
        $object->id = 1;
        $object->name = 'test';

        $objectToPersist = new ObjectToPersist(
            persisterClass: HashPersister::class,
            operation: 'doPersist',
            redisKey: 'test:1',
            converter: new HashObjectConverter(),
            value: $object,
        );

        $persister->doPersist([$objectToPersist]);
    }

    public function testDoPersistWithTtlCallsExpire(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())->method('hMSet');
        $redisClient->expects($this->once())->method('expire')->with('test:1', 3600);

        $persister = new HashPersister(redis: $redisClient);

        $object = new PersisterTestDummy();
        $object->id = 1;

        $objectToPersist = new ObjectToPersist(
            persisterClass: HashPersister::class,
            operation: 'doPersist',
            redisKey: 'test:1',
            converter: new HashObjectConverter(),
            value: $object,
            ttl: 3600,
        );

        $persister->doPersist([$objectToPersist]);
    }

    public function testDoPersistWithEmptyArray(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->never())->method('hMSet');

        $persister = new HashPersister(redis: $redisClient);
        $persister->doPersist([]);
    }

    public function testDoDeleteCallsDel(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())->method('del');

        $persister = new HashPersister(redis: $redisClient);

        $objectToRemove = new ObjectToPersist(
            persisterClass: HashPersister::class,
            operation: 'doDelete',
            redisKey: 'test:1',
        );

        $persister->doDelete([$objectToRemove]);
    }

    public function testPersistGeneratesIdWhenNull(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $persister = new HashPersister(redis: $redisClient);

        $entity = new Entity();
        $object = new PersisterTestDummy();
        // id is null

        $result = $persister->persist($entity, $object);

        $this->assertNotNull($object->id);
        $this->assertStringContainsString((string) $object->id, $result->redisKey);
    }
}

#[RedisOm\Entity]
class PersisterTestDummy
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $name = '';
}
