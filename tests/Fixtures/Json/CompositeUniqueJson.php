<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Json;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
#[RedisOm\Unique(properties: ['username', 'tenantId'])]
class CompositeUniqueJson
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public string $username = '';

    #[RedisOm\Property(index: true)]
    public int $tenantId = 0;

    #[RedisOm\Property]
    public string $email = '';
}
