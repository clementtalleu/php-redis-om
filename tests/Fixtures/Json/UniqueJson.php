<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Json;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class UniqueJson
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    #[RedisOm\Unique]
    public string $email = '';

    #[RedisOm\Property]
    public string $name = '';
}
