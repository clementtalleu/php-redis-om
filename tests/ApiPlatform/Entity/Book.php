<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\ApiPlatform\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Talleu\RedisOm\ApiPlatform\Filters\SearchFilter;
use Talleu\RedisOm\ApiPlatform\State\RedisProcessor;
use Talleu\RedisOm\ApiPlatform\State\RedisProvider;
use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity]
#[ApiResource(
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationPartial: true,
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
class Book
{
    #[RedisOm\Id]
    #[RedisOm\Property(index: true)]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public ?string $name = null;
}
