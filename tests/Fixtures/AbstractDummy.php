<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures;

use Talleu\RedisOm\Om\Mapping as RedisOm;

abstract class AbstractDummy
{
    #[RedisOm\Id]
    #[RedisOm\Property(index: true)]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = 'test';

    #[RedisOm\Property(index: true)]
    public ?\DateTime $createdAt = null;

    #[RedisOm\Property(index: true)]
    public ?\DateTimeImmutable $createdAtImmutable = null;

    #[RedisOm\Property(index: true)]
    public ?int $age = null;

    #[RedisOm\Property]
    public ?float $price = null;

    #[RedisOm\Property(index: true)]
    public ?Bar $bar = null;

    #[RedisOm\Property]
    public ?array $infos = [];

    #[RedisOm\Property]
    public ?bool $enabled = null;

    #[RedisOm\Property]
    /**
     * @var \DateTime[]
     */
    public ?array $datesArray = [];

    #[RedisOm\Property]
    public ?array $complexData = [];

    public static function create(
        ?int                $id,
        ?int                $age,
        ?float              $price,
        ?string             $name,
        ?\DateTime         $createdAt = null,
        ?\DateTimeImmutable $createdAtImmutable = null,
        ?Bar               $bar = null,
        array              $infos = [],
        array              $datesArray = [],
        bool               $enabled = true,
        array              $complexData = [],
    ): self {
        $dummy = new static();
        $dummy->id = $id;
        $dummy->age = $age;
        $dummy->name = $name;
        $dummy->price = $price;
        $dummy->createdAt = $createdAt;
        $dummy->createdAtImmutable = $createdAtImmutable;
        $dummy->infos = $infos;
        $dummy->enabled = $enabled;
        $dummy->datesArray = $datesArray;
        $dummy->complexData = $complexData;
        $dummy->bar = $bar;

        return $dummy;
    }
}
