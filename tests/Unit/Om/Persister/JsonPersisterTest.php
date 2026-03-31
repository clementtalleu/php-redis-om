<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Persister;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\JsonModel\JsonObjectConverter;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Persister\JsonModel\JsonPersister;
use Talleu\RedisOm\Om\Persister\ObjectToPersist;
use Talleu\RedisOm\Om\RedisFormat;

final class JsonPersisterTest extends TestCase
{
    public function testDoPersistSingleObjectCallsJsonSet(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())->method('jsonSet');

        $persister = new JsonPersister(redis: $redisClient);

        $object = new JsonPersisterTestDummy();
        $object->id = 1;
        $object->name = 'test';

        $objectToPersist = new ObjectToPersist(
            persisterClass: JsonPersister::class,
            operation: 'doPersist',
            redisKey: 'test:1',
            converter: new JsonObjectConverter(),
            value: $object,
        );

        $persister->doPersist([$objectToPersist]);
    }

    public function testDoPersistSingleObjectWithTtlCallsExpire(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())->method('jsonSet');
        $redisClient->expects($this->once())->method('expire')->with('test:1', 1800);

        $persister = new JsonPersister(redis: $redisClient);

        $object = new JsonPersisterTestDummy();
        $object->id = 1;

        $objectToPersist = new ObjectToPersist(
            persisterClass: JsonPersister::class,
            operation: 'doPersist',
            redisKey: 'test:1',
            converter: new JsonObjectConverter(),
            value: $object,
            ttl: 1800,
        );

        $persister->doPersist([$objectToPersist]);
    }

    public function testDoPersistMultipleObjectsCallsJsonMSet(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())->method('jsonMSet');
        $redisClient->expects($this->never())->method('jsonSet');

        $persister = new JsonPersister(redis: $redisClient);
        $converter = new JsonObjectConverter();

        $object1 = new JsonPersisterTestDummy();
        $object1->id = 1;
        $object1->name = 'first';

        $object2 = new JsonPersisterTestDummy();
        $object2->id = 2;
        $object2->name = 'second';

        $persister->doPersist([
            new ObjectToPersist(JsonPersister::class, 'doPersist', 'test:1', $converter, $object1),
            new ObjectToPersist(JsonPersister::class, 'doPersist', 'test:2', $converter, $object2),
        ]);
    }

    public function testDoPersistMultipleObjectsWithTtlCallsExpire(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())->method('jsonMSet');
        $redisClient->expects($this->exactly(2))->method('expire');

        $persister = new JsonPersister(redis: $redisClient);
        $converter = new JsonObjectConverter();

        $object1 = new JsonPersisterTestDummy();
        $object1->id = 1;
        $object2 = new JsonPersisterTestDummy();
        $object2->id = 2;

        $persister->doPersist([
            new ObjectToPersist(JsonPersister::class, 'doPersist', 'test:1', $converter, $object1, 3600),
            new ObjectToPersist(JsonPersister::class, 'doPersist', 'test:2', $converter, $object2, 3600),
        ]);
    }

    public function testDoPersistWithEmptyArray(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->never())->method('jsonSet');
        $redisClient->expects($this->never())->method('jsonMSet');

        $persister = new JsonPersister(redis: $redisClient);
        $persister->doPersist([]);
    }

    public function testDoDeleteCallsJsonDel(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->once())->method('jsonDel');

        $persister = new JsonPersister(redis: $redisClient);

        $objectToRemove = new ObjectToPersist(
            persisterClass: JsonPersister::class,
            operation: 'doDelete',
            redisKey: 'test:1',
        );

        $persister->doDelete([$objectToRemove]);
    }

    public function testDoDeleteMultiple(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);
        $redisClient->expects($this->exactly(3))->method('jsonDel');

        $persister = new JsonPersister(redis: $redisClient);

        $persister->doDelete([
            new ObjectToPersist(JsonPersister::class, 'doDelete', 'test:1'),
            new ObjectToPersist(JsonPersister::class, 'doDelete', 'test:2'),
            new ObjectToPersist(JsonPersister::class, 'doDelete', 'test:3'),
        ]);
    }
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class JsonPersisterTestDummy
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $name = '';
}
