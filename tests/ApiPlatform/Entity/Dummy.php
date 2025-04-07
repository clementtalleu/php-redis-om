<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\ApiPlatform\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Talleu\RedisOm\ApiPlatform\Filters\BooleanFilter;
use Talleu\RedisOm\ApiPlatform\Filters\NumericFilter;
use Talleu\RedisOm\ApiPlatform\Filters\OrderFilter;
use Talleu\RedisOm\ApiPlatform\Filters\SearchFilter;
use Talleu\RedisOm\ApiPlatform\State\RedisProcessor;
use Talleu\RedisOm\ApiPlatform\State\RedisProvider;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;

#[RedisOm\Entity]
#[ApiResource(
    provider: RedisProvider::class,
    processor: RedisProcessor::class,
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'exact', 'partialName' => 'partial'])]
#[ApiFilter(NumericFilter::class, properties: ['age', 'price'])]
#[ApiFilter(BooleanFilter::class, properties: ['enabled'])]
#[ApiFilter(OrderFilter::class, properties: ['age', 'id', 'name'])]
class Dummy extends DummyHash
{
    #[RedisOm\Property(index: true)]
    public ?string $partialName = 'Martin';
}
