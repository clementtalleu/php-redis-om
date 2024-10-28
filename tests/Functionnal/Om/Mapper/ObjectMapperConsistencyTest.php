<?php

namespace Talleu\RedisOm\Tests\Functionnal\Om\Mapper;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\BarConstructor;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class ObjectMapperConsistencyTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createClient());
        parent::setUp();
    }

    public function testObjectMappingWithConstructor(): void
    {
        self::emptyRedis();
        self::generateIndex();

        $bar = $this->createBar(1, 'Title');

        $this->objectManager->persist($bar);
        $this->objectManager->flush();
        $this->assertEquals($bar, $this->objectManager->find(BarConstructor::class, 1));
    }

    public function createBar(int $id, string $title): BarConstructor
    {
        $bar = new BarConstructor(
            $id,
            $title,
            updatedAt: new \DateTime('2021-01-01'),
        );

        return $bar;
    }
}
