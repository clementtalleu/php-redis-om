<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Merge;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\Fixtures\Json\DummyJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class MergeTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(self::createRedisClient());
        parent::setUp();
    }

    public function testMergeHashPartialUpdate(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        // Load, modify one field, merge
        $object = $this->objectManager->find(DummyHash::class, 1);
        $originalAge = $object->age;
        $object->name = 'MergedName';

        $this->objectManager->merge($object);
        $this->objectManager->flush();
        $this->objectManager->clear();

        // Verify the change persisted and other fields untouched
        $reloaded = $this->objectManager->find(DummyHash::class, 1);
        $this->assertSame('MergedName', $reloaded->name);
        $this->assertSame($originalAge, $reloaded->age);
    }

    public function testMergeJsonPartialUpdate(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures(DummyJson::class);

        $object = $this->objectManager->find(DummyJson::class, 1);
        $originalAge = $object->age;
        $object->name = 'MergedJson';

        $this->objectManager->merge($object);
        $this->objectManager->flush();
        $this->objectManager->clear();

        $reloaded = $this->objectManager->find(DummyJson::class, 1);
        $this->assertSame('MergedJson', $reloaded->name);
        $this->assertSame($originalAge, $reloaded->age);
    }

    public function testMergeNoChangeDoesNothing(): void
    {
        static::emptyRedis();
        static::generateIndex();
        static::loadRedisFixtures();

        $object = $this->objectManager->find(DummyHash::class, 1);
        $originalName = $object->name;

        // Merge without changing anything
        $this->objectManager->merge($object);
        $this->objectManager->flush();
        $this->objectManager->clear();

        $reloaded = $this->objectManager->find(DummyHash::class, 1);
        $this->assertSame($originalName, $reloaded->name);
    }

    public function testMergeNewObjectFallsToPersist(): void
    {
        static::emptyRedis();
        static::generateIndex();

        $object = new DummyHash();
        $object->id = 999;
        $object->name = 'BrandNew';
        $object->age = 42;
        $object->price = 9.99;

        // Merge on a new object (no snapshot) should do full persist
        $this->objectManager->merge($object);
        $this->objectManager->flush();
        $this->objectManager->clear();

        $found = $this->objectManager->find(DummyHash::class, 999);
        $this->assertNotNull($found);
        $this->assertSame('BrandNew', $found->name);
        $this->assertSame(42, $found->age);
    }
}
