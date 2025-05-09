<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\ApiPlatform\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\QueryParameter;
use Talleu\RedisOm\ApiPlatform\Filters\BooleanFilter;
use Talleu\RedisOm\ApiPlatform\Filters\ExactSearchFilter;
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
#[QueryParameter(key: 'name', filter: new ExactSearchFilter())]
#[QueryParameter(key: 'partialName', filter: new SearchFilter())]
#[QueryParameter(key: 'age', filter: new NumericFilter())]
#[QueryParameter(key: 'price', filter: new NumericFilter())]
#[QueryParameter(key: 'enabled', filter: new BooleanFilter())]
#[QueryParameter(key: 'order[:property]', filter: new OrderFilter(properties: ['age', 'id', 'name']))]
class Dummy extends DummyHash
{
    #[RedisOm\Property(index: true)]
    public ?string $partialName = 'Martin';
}
