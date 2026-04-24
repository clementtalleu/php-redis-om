<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Json;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Tests\Fixtures\PriorityEnum;
use Talleu\RedisOm\Tests\Fixtures\StatusEnum;

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class EnumDummyJson
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $name = '';

    #[RedisOm\Property(index: true)]
    public StatusEnum $status = StatusEnum::PENDING;

    #[RedisOm\Property]
    public PriorityEnum $priority = PriorityEnum::LOW;
}
