<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures;

use Fixtures\Hash\ExpirationDummyHash;

class FixturesGenerator
{
    public static function generateDummies($dummyClass): array
    {
        $dummy =  $dummyClass::create(
            id: 1,
            age: 20,
            price: 10.5,
            name: 'Olivier',
            createdAt: new \DateTime('2022-01-01 00:00:00'),
            createdAtImmutable: new \DateTimeImmutable('2021-01-01 00:00:00'),
            bar: Bar::create(1, 'bar', ['type1', 'type2'], new \DateTime('2021-01-01 00:00:00')),
            infos: ['info1' => ['key' => 'value', 'date' => new \DateTime('now')], 'info2' => 'value2'],
            datesArray: [new \DateTime('2018-01-01 00:00:00'), new \DateTime('2015-01-02 00:00:00')],
            enabled: true,
            complexData: [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => [
                    'key4' => 'value4',
                    'key5' => 'value5',
                ],
            ]
        );

        $dummy2 = $dummyClass::create(
            id: 2,
            age: 18,
            price: 14.5,
            name: 'Kevin',
            createdAt: new \DateTime('2018-05-01 00:00:00'),
            bar: Bar::create(2, 'Hello', ['foo', 'toto'], new \DateTime('1999-01-01 00:00:00')),
            enabled: false
        );

        $dummy3 = $dummyClass::create(
            id: 3,
            age: 34,
            price: 99.3,
            name: 'Olivier',
            createdAt: new \DateTime('2014-02-12 00:00:00'),
        );

        return [
            $dummy,
            $dummy2,
            $dummy3
        ];
    }
}
