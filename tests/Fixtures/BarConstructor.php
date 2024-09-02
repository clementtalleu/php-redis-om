<?php

namespace Talleu\RedisOm\Tests\Fixtures;

use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity()]
class BarConstructor
{
    public function __construct(
        #[RedisOm\Property]
        #[RedisOm\Id]
        public readonly ?int                $id = null,
        #[RedisOm\Property(index: true)]
        public ?string             $title = null,
        #[RedisOm\Property]
        public ?array              $types = null,
        #[RedisOm\Property]
        public ?\DateTimeInterface $updatedAt = null)
    {
    }
}
