<?php

namespace Talleu\RedisOm\Tests\Fixtures;

use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity]
class Bar
{
    #[RedisOm\Property]
    #[RedisOm\Id]
    public ?int $id = null;
    #[RedisOm\Property]
    public ?string $title = null;
    #[RedisOm\Property]
    public ?array $types = null;
    #[RedisOm\Property]
    public ?\DateTimeInterface $updatedAt = null;

    public static function create(
        ?int                 $id,
        ?string              $title,
        ?array              $types = null,
        ?\DateTimeInterface $updatedAt = null
    ): self {
        $bar = new self();
        $bar->id = $id;
        $bar->title = $title;
        $bar->types = $types;
        $bar->updatedAt = $updatedAt;

        return $bar;
    }
}
