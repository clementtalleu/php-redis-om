<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Hash;

use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity]
class UniqueHash
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
