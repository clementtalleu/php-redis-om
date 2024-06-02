<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Hash;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Tests\Fixtures\AbstractArray;

#[RedisOm\Entity(format: RedisFormat::HASH)]
class ArrayHash extends AbstractArray
{
}
