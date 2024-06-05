<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures;

use Talleu\RedisOm\Om\Mapping as RedisOm;

abstract class AbstractUser
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?string $id = null;

    #[RedisOm\Property]
    public ?string $email = null;

    #[RedisOm\Property]
    public ?string $nickname = null;

    public static function create(
        ?string $id,
        ?string $email,
        ?string $nickname,
    ): static {
        $user = new static();
        $user->id = $id;
        $user->email = $email;
        $user->nickname = $nickname;

        return $user;
    }
}
