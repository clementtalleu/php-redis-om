# 🕷 API Platform support (beta)


You are an API Platform user, congratulations ;)

An implementation of API Platform's main features is provided, enabling you to use the library without a SQL database and only with Redis.

If your project is not an API Platform project, you can simply add it as follows:

```
composer require api-platform/core
```

Then simply use the traditional API Platform attributes, specifying the Redis provider and processor :
```php

use ApiPlatform\Metadata\ApiResource;
use Talleu\RedisOm\Bundle\ApiPlatform\State\RedisProcessor;
use Talleu\RedisOm\Bundle\ApiPlatform\State\RedisProvider;
use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity]
#[ApiResource(
    provider: RedisProvider::class,
    processor: RedisProcessor::class,
)]
class Book
{
    #[RedisOm\Id]
    #[RedisOm\Property(index: true)]
    public ?int $id = null;

    #[RedisOm\Property(index: true)]
    public ?string $name = null;
}

```

And that's enough!

You can take advantage of the basic API Platform tools: serialization, pagination, persistence, and several Redis-specific filters have been developed:
```
SearchFilter
NumericFilter
BooleanFilter
OrderFilter
```

Implement them as follows:

```php

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Talleu\RedisOm\Bundle\ApiPlatform\Filters\BooleanFilter;
use Talleu\RedisOm\Bundle\ApiPlatform\Filters\NumericFilter;
use Talleu\RedisOm\Bundle\ApiPlatform\Filters\OrderFilter;
use Talleu\RedisOm\Bundle\ApiPlatform\Filters\SearchFilter;
use Talleu\RedisOm\Bundle\ApiPlatform\State\RedisProcessor;
use Talleu\RedisOm\Bundle\ApiPlatform\State\RedisProvider;
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
class Book
{}
```

And using them couldn't be easier if you're familiar with API Platform.

```
/api/books?name=capital&order['age']=DESC
```

For more information on the framework: https://api-platform.com/docs/symfony/

Some features are not available yet, so please don't hesitate to contribute. 

