<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Hash;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Tests\Fixtures\PriorityEnum;
use Talleu\RedisOm\Tests\Fixtures\StatusEnum;

#[RedisOm\Entity]
class EnumDummyHash
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = '';

    #[RedisOm\Property(index: true)]
    public StatusEnum $status = StatusEnum::PENDING;

    #[RedisOm\Property(index: true)]
    public PriorityEnum $priority = PriorityEnum::LOW;
}
