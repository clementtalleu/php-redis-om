<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Hash;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Tests\Fixtures\AbstractDummyWithPrivateProperties;

#[RedisOm\Entity()]
class DummyHashWithPrivateProperties extends AbstractDummyWithPrivateProperties
{
}
