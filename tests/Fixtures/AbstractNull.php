<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures;

use Talleu\RedisOm\Om\Mapping as RedisOm;

abstract class AbstractNull
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public ?string $unknown = null;

    public static function create(
        ?int $id,
    ): static {
        $foo = new static();
        $foo->id = $id;

        return $foo;
    }
}
