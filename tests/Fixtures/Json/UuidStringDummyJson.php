<?php declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Json;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Tests\Fixtures\AbstractDummy;

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class UuidStringDummyJson extends AbstractDummy
{
    #[RedisOm\Property(index: true)]
    public string $specialChars = '1f0f2176-5dae-6524-8d77-dbe79e5a7b83';
}
