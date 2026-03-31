<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Om\Repository\HashModel\HashRepository;
use Talleu\RedisOm\Om\Repository\JsonModel\JsonRepository;

final class RedisObjectManagerTest extends TestCase
{
    private RedisClientInterface $redisClient;
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->redisClient = $this->createMock(RedisClientInterface::class);
        $this->objectManager = new RedisObjectManager($this->redisClient);
    }

    public function testGetRepositoryReturnsHashRepositoryForHashEntity(): void
    {
        $repo = $this->objectManager->getRepository(OMTestHashEntity::class);
        $this->assertInstanceOf(HashRepository::class, $repo);
    }

    public function testGetRepositoryReturnsJsonRepositoryForJsonEntity(): void
    {
        $repo = $this->objectManager->getRepository(OMTestJsonEntity::class);
        $this->assertInstanceOf(JsonRepository::class, $repo);
    }

    public function testPersistAddsObjectToUnitOfWork(): void
    {
        $object = new OMTestHashEntity();
        $object->id = 1;
        $object->name = 'test';

        $this->objectManager->persist($object);
        $this->assertTrue($this->objectManager->contains($object));
    }

    public function testClearEmptiesUnitOfWork(): void
    {
        $object = new OMTestHashEntity();
        $object->id = 1;
        $object->name = 'test';

        $this->objectManager->persist($object);
        $this->objectManager->clear();

        $this->assertFalse($this->objectManager->contains($object));
    }

    public function testDetachRemovesSpecificObject(): void
    {
        $object1 = new OMTestHashEntity();
        $object1->id = 1;
        $object1->name = 'first';

        $object2 = new OMTestHashEntity();
        $object2->id = 2;
        $object2->name = 'second';

        $this->objectManager->persist($object1);
        $this->objectManager->persist($object2);

        $this->objectManager->detach($object1);

        $this->assertFalse($this->objectManager->contains($object1));
        $this->assertTrue($this->objectManager->contains($object2));
    }

    public function testFlushCallsPersister(): void
    {
        $this->redisClient->expects($this->atLeastOnce())
            ->method('hMSet');

        $object = new OMTestHashEntity();
        $object->id = 1;
        $object->name = 'test';

        $this->objectManager->persist($object);
        $this->objectManager->flush();
    }

    public function testFlushWithRemoveCallsDelete(): void
    {
        $this->redisClient->expects($this->atLeastOnce())
            ->method('del');

        $object = new OMTestHashEntity();
        $object->id = 1;
        $object->name = 'test';

        $this->objectManager->remove($object);
        $this->objectManager->flush();
    }

    public function testPersistAndFlushJson(): void
    {
        $this->redisClient->expects($this->atLeastOnce())
            ->method('jsonSet');

        $object = new OMTestJsonEntity();
        $object->id = 1;
        $object->name = 'test';

        $this->objectManager->persist($object);
        $this->objectManager->flush();
    }

    public function testFlushClearsUnitOfWorkForFlushedOperations(): void
    {
        $this->redisClient->method('hMSet');

        $object = new OMTestHashEntity();
        $object->id = 1;
        $object->name = 'test';

        $this->objectManager->persist($object);
        $this->objectManager->flush();

        // After flush, the object should no longer be in the unit of work
        $this->assertFalse($this->objectManager->contains($object));
    }

    public function testFindDelegatesToRepository(): void
    {
        $this->redisClient->expects($this->once())
            ->method('hGetAll')
            ->willReturn(['id' => '1', 'name' => 'found']);

        $result = $this->objectManager->find(OMTestHashEntity::class, 1);

        $this->assertInstanceOf(OMTestHashEntity::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame('found', $result->name);
    }

    public function testFindReturnsNullWhenNotFound(): void
    {
        $this->redisClient->expects($this->once())
            ->method('hGetAll')
            ->willReturn([]);

        $result = $this->objectManager->find(OMTestHashEntity::class, 999);

        $this->assertNull($result);
    }

    public function testPersistWithoutEntityAttributeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('#[RedisOm\Entity]');

        $object = new \stdClass();
        $this->objectManager->persist($object);
    }

    public function testGetRedisClient(): void
    {
        $this->assertSame($this->redisClient, $this->objectManager->getRedisClient());
    }

    public function testGetEventManager(): void
    {
        $this->assertNotNull($this->objectManager->getEventManager());
    }

    public function testPersistWithNullIdGeneratesOne(): void
    {
        $this->redisClient->method('hMSet');

        $object = new OMTestHashEntity();
        $object->name = 'auto-id';

        $this->objectManager->persist($object);
        $this->objectManager->flush();

        $this->assertNotNull($object->id);
    }
}

#[RedisOm\Entity]
class OMTestHashEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = '';
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class OMTestJsonEntity
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = '';
}
