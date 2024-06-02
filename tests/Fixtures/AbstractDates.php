<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures;

use Talleu\RedisOm\Om\Mapping as RedisOm;

abstract class AbstractDates
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public ?\DateTime $createdAt = null;

    #[RedisOm\Property]
    public ?\DateTime $createdAtImmutable = null;
}