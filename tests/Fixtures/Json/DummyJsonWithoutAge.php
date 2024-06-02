<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Json;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Tests\Fixtures\AbstractDummy;

#[RedisOm\Entity]
class DummyJsonWithoutAge extends AbstractDummy
{
    public ?int $age = null;
}
