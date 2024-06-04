<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures;

use Talleu\RedisOm\Om\Mapping as RedisOm;


class AbstractDummyWithPrivateProperties
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(getter: 'obtainName', setter: 'fillName')]
    protected string $name = 'test';

    #[RedisOm\Property]
    protected ?int $age = null;

    public function obtainName(): string
    {
        return $this->name;
    }

    public function fillName(string $name): void
    {
        $this->name = $name;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): void
    {
        $this->age = $age;
    }

    public static function create(
        ?int                $id,
        ?string             $name,
        ?int                $age,
    ): self
    {
        $dummy = new static();
        $dummy->id = $id;
        $dummy->setAge($age);
        $dummy->fillName($name);

        return $dummy;
    }
}
