<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\JsonModel;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Json\EnumDummyJson;
use Talleu\RedisOm\Tests\Fixtures\PriorityEnum;
use Talleu\RedisOm\Tests\Fixtures\StatusEnum;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class EnumRepositoryTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(self::createRedisClient());
        parent::setUp();
    }

    public function testPersistAndFindWithEnums(): void
    {
        static::emptyRedis();
        static::generateIndex();

        $entity = new EnumDummyJson();
        $entity->id = 1;
        $entity->name = 'Task 1';
        $entity->status = StatusEnum::ACTIVE;
        $entity->priority = PriorityEnum::HIGH;

        $this->objectManager->persist($entity);
        $this->objectManager->flush();
        $this->objectManager->clear();

        $found = $this->objectManager->find(EnumDummyJson::class, 1);

        $this->assertInstanceOf(EnumDummyJson::class, $found);
        $this->assertSame(StatusEnum::ACTIVE, $found->status);
        $this->assertSame(PriorityEnum::HIGH, $found->priority);
    }

    public function testFindByEnum(): void
    {
        static::emptyRedis();
        static::generateIndex();

        $entity1 = new EnumDummyJson();
        $entity1->id = 1;
        $entity1->name = 'Active';
        $entity1->status = StatusEnum::ACTIVE;

        $entity2 = new EnumDummyJson();
        $entity2->id = 2;
        $entity2->name = 'Inactive';
        $entity2->status = StatusEnum::INACTIVE;

        $this->objectManager->persist($entity1);
        $this->objectManager->persist($entity2);
        $this->objectManager->flush();
        $this->objectManager->clear();

        $repo = $this->objectManager->getRepository(EnumDummyJson::class);
        $results = $repo->findBy(['status' => 'active']);

        $this->assertCount(1, $results);
        $this->assertSame(StatusEnum::ACTIVE, $results[0]->status);
    }

    public function testEnumRoundTripAllValues(): void
    {
        static::emptyRedis();
        static::generateIndex();

        foreach (StatusEnum::cases() as $i => $status) {
            $entity = new EnumDummyJson();
            $entity->id = $i + 10;
            $entity->name = $status->value;
            $entity->status = $status;
            $this->objectManager->persist($entity);
        }
        $this->objectManager->flush();
        $this->objectManager->clear();

        foreach (StatusEnum::cases() as $i => $status) {
            $found = $this->objectManager->find(EnumDummyJson::class, $i + 10);
            $this->assertSame($status, $found->status);
        }
    }
}
