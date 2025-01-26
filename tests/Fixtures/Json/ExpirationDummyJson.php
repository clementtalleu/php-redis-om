<?php

declare(strict_types=1);

namespace Fixtures\Json;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Tests\Fixtures\AbstractDummy;

#[RedisOm\Entity(format: RedisFormat::JSON->value, ttl: 2)]
class ExpirationDummyJson extends AbstractDummy
{
}
