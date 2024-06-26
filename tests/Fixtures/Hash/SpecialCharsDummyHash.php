<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Hash;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Tests\Fixtures\AbstractDummy;

#[RedisOm\Entity]
class SpecialCharsDummyHash extends AbstractDummy
{
    #[RedisOm\Property(index: true)]
    public string $specialChars = 'ok:';
}
