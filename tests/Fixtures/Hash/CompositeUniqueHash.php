<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Hash;

use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity]
#[RedisOm\Unique(properties: ['username', 'tenantId'])]
class CompositeUniqueHash
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
