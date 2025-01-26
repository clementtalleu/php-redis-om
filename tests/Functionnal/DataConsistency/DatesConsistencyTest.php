<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\DataConsistency;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DateHash;
use Talleu\RedisOm\Tests\Fixtures\Json\DateJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class DatesConsistencyTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createClient());
        parent::setUp();
    }

    public function testDateHash(): void
    {
        self::emptyRedis();
        self::generateIndex();

        $dateObject = new DateHash();
        $dateObject->id = 1;
        $dateObject->createdAt = new \DateTime('2021-01-01');


        $this->objectManager->persist($dateObject);
        $this->objectManager->flush();

        $this->assertEquals($dateObject, $this->objectManager->find(DateHash::class, 1));
    }

    public function testDateJson(): void
    {
        self::emptyRedis();
        self::generateIndex();

        $dateObject = new DateJson();
        $dateObject->id = 1;
        $dateObject->createdAt = new \DateTime('2021-01-01');


        $this->objectManager->persist($dateObject);
        $this->objectManager->flush();

        $this->assertEquals($dateObject, $this->objectManager->find(DateJson::class, 1));
    }
}
