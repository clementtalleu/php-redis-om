<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Json;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Tests\Fixtures\AbstractDummy;

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class SpecialCharsDummyJson extends AbstractDummy
{
    #[RedisOm\Property(index: true)]
    public string $specialChars = 'ok:';
}
