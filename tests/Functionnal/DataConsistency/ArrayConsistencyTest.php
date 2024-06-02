<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\DataConsistency;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Bar;
use Talleu\RedisOm\Tests\Fixtures\Hash\ArrayHash;
use Talleu\RedisOm\Tests\Fixtures\Hash\DateHash;
use Talleu\RedisOm\Tests\Fixtures\Json\ArrayJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class ArrayConsistencyTest extends RedisAbstractTestCase
{
    // public function testArrayHash(): void
    // {
    //     self::emptyRedis();
    //     self::generateIndex();
    //
    //     $arrayHash = new ArrayHash();
    //     $arrayHash->id = 1;
    //     $arrayHash->data = [
    //         'createdAt' => new \DateTime('2021-01-01'),
    //         'foo' => [
    //             'bar' => 'baz',
    //             0 => 'test',
    //         ],
    //         'types' => [
    //             'key' => 'value',
    //             'bar1' => $this->createBar(1, 'Title'),
    //             'bar2' => $this->createBar(2, 'Title2'),
    //         ]
    //     ];
    //
    //     $objectManager = new RedisObjectManager();
    //     $objectManager->persist($arrayHash);
    //     $objectManager->flush();
    //
    //     dump($arrayHash);
    //     dump($objectManager->find(ArrayHash::class, 1));
    //     die;
    //
    //     $this->assertEquals($arrayHash, $objectManager->find(ArrayHash::class, 1));
    // }

    public function testArrayJson(): void
    {
        self::emptyRedis();
        self::generateIndex();

        $arrayJson = new ArrayJson();
        $arrayJson->id = 1;
        $arrayJson->data = [
            'createdAt' => new \DateTime('2021-01-01'),
            'foo' => [
                'bar' => 'baz',
                0 => 'test',
                2 => 33.4,
            ],
            'infos' => [
                'key' => ['foo', 'bar'],
                'bar1' => $this->createBar(1, 'Title'),
                'bar2' => $this->createBar(2, 'Title2'),
            ]
        ];

        $objectManager = new RedisObjectManager();
        $objectManager->persist($arrayJson);
        $objectManager->flush();

        $this->assertEquals($arrayJson, $objectManager->find(ArrayJson::class, 1));
    }

    public function createBar(int $id, string $title): Bar
    {
        $bar = new Bar();
        $bar->id = $id;
        $bar->title = $title;
        $bar->updatedAt = new \DateTime('2021-01-01');

        return $bar;
    }
}
