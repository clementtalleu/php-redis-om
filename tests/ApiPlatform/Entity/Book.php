<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\ApiPlatform\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\QueryParameter;
use Talleu\RedisOm\Bundle\ApiPlatform\Filters\SearchFilter;
use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity]
#[ApiResource(
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationPartial: true,
)]
class Book
{
    #[RedisOm\Id]
    #[RedisOm\Property(index: true)]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public ?string $name = null;
}
