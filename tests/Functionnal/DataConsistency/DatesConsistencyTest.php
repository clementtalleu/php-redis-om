<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DateHash;
use Talleu\RedisOm\Tests\Fixtures\Json\DateJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class DatesConsistencyTest extends RedisAbstractTestCase
{
    // public function testDateHash(): void
    // {
    //     self::emptyRedis();
    //     self::generateIndex();
    //
    //     $dateObject = new DateHash();
    //     $dateObject->id = 1;
    //     $dateObject->createdAt = new \DateTime('2021-01-01');
    //
    //     $objectManager = new RedisObjectManager();
    //     $objectManager->persist($dateObject);
    //     $objectManager->flush();
    //
    //     $this->assertEquals($dateObject, $objectManager->find(DateHash::class, 1));
    // }

    public function testDateJson(): void
    {
        self::emptyRedis();
        self::generateIndex();

        $dateObject = new DateJson();
        $dateObject->id = 1;
        $dateObject->createdAt = new \DateTime('2021-01-01');

        $objectManager = new RedisObjectManager();
        $objectManager->persist($dateObject);
        $objectManager->flush();

        $this->assertEquals($dateObject, $objectManager->find(DateJson::class, 1));
    }
}
