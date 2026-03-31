
[![Build Status](https://github.com/clementtalleu/php-redis-om/actions/workflows/tests.yaml/badge.svg)](https://github.com/clementtalleu/php-redis-om/actions)
![PHPStan](https://img.shields.io/badge/PHPStan-OK-brightgreen)
[![Packagist Version](https://img.shields.io/packagist/v/talleu/php-redis-om.svg)](https://packagist.org/packages/talleu/php-redis-om)
[![GitHub](https://img.shields.io/github/license/clementtalleu/php-redis-om.svg)](https://github.com/averias/phpredis-json)
[![codecov.io Code Coverage](https://img.shields.io/codecov/c/github/clementtalleu/php-redis-om.svg)](https://codecov.io/github/clementtalleu/php-redis-om?branch=main)

# php-redis-om 🗄️

A PHP object mapper for [Redis](https://redis.io/).

An Object Mapper for Redis®, designed to providing an intuitive and familiar interface for PHP developers to interact
with Redis.

## Features 🛠️

- Doctrine-like methods and architecture
- Symfony bundle integration
- Easy integration with existing PHP applications
- High performance and scalability with Redis®
- Support for Redis JSON module
- Automatic schema generation
- Search and query capabilities with range filters
- Auto-expiration of your objects
- PHP enum support (backed enums)
- Identity map and dirty tracking with partial updates
- Atomic transactions (MULTI/EXEC)
- Pagination with total count
- GEO queries (radius search)
- Pipeline batch reads
- API Platform support (beta)

## Requirements ⚙️

- PHP 8.2 or higher
- Redis 4.0 or higher
- Redisearch module (available by default with Redis >8 or in redis-stack distribution) ([installation](https://redis.io/docs/latest/operate/oss_and_stack/install/install-stack/))
- php-redis extension OR Predis library
- Redis JSON module (optional, include in redis-stack)
- Composer

## Supported types ✅

- scalar (string, int, float, bool, double)
- PHP backed enums (string and int)
- timestamp
- json
- null
- DateTimeImmutable
- DateTime
- array and nested arrays
- object and nested objects
- stdClass

## Installation 📝

Install the library via Composer:

```console
composer require talleu/php-redis-om
```

Depending on your configuration, use phpredis or Predis

## Symfony bundle 🎵

In a Symfony application, you may need to add this line to config/bundles.php
```console
    Talleu\RedisOm\Bundle\TalleuRedisOmBundle::class => ['all' => true],
```

And that's it, your installation is complete ! 🚀

## API Platform support 🕷️

For API Platform users, a basic implementation is provided here: [API Platfom X Redis](docs/api_platform.md)
 
## Basic Usage 🎯

Add the RedisOm attribute to your class to map it to a Redis schema:

```php
<?php

use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity]
class User
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public int $id;

    #[RedisOm\Property(index:true)]
    public string $name;

    #[RedisOm\Property]
    public \DateTimeImmutable $createdAt;
}
```

After add the RedisOm attribute to your class,
you have to run the following command to create the Redis schema for your classes (default path is `./src`): 

For Symfony users:

```console
bin/console redis-om:migrate 
```

For others PHP applications:

```console
vendor/bin/redisMigration <YOUR DIRECTORY PATH>
```

Then you can use the ObjectManager to persist your objects from Redis ! 💪

For Symfony users, just inject the RedisObjectManagerInterface in the constructor:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;
use App\Entity\Book;

class MySymfonyController extends AbstractController
{
    public function __construct(private RedisObjectManagerInterface $redisObjectManager)
    {}
    
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $book = new Book();
        $book->name = 'Martin Eden';
        $this->redisObjectManager->persist($book);
        $this->redisObjectManager->flush();

       //..
    }
}
```

For others PHP applications:

```php
<?php

use Talleu\RedisOm\Om\RedisObjectManager;

$user = new User()
$user->id = 1;
$user->name = 'John Doe';

// Persist the object in redis
$objectManager = new RedisObjectManager();
$objectManager->persist($user);
$objectManager->flush();
```

🥳 Congratulations, your PHP object is now registered in Redis !


You can now retrieve your user wherever you like using the repository provided by the Object Manager (or the object manager directly):

```php
// Retrieve the object from redis 
$user = $this->redisObjectManager->find(User::class, 1);
$user = $this->redisObjectManager->getRepository(User::class)->find(1);
$user = $this->redisObjectManager->getRepository(User::class)->findOneBy(['name' => 'John Doe']);

// Retrieve a collection of objects
$users = $this->redisObjectManager->getRepository(User::class)->findAll();
$users = $this->redisObjectManager->getRepository(User::class)->findBy(['name' => 'John Doe'], ['createdAt' => 'DESC'], 10);
```


## Enum Support 🏷️

PHP backed enums are natively supported:

```php
enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

#[RedisOm\Entity]
class Task
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public int $id;

    #[RedisOm\Property(index: true)]
    public Status $status;
}
```

Search by enum value:
```php
$activeTasks = $repository->findBy(['status' => 'active']);
```

## Range Queries 🔢

Use MongoDB-style operators for numeric range searches:

```php
// Age between 18 and 65
$users = $repository->findBy(['age' => ['$gte' => 18, '$lte' => 65]]);

// Price greater than 100
$products = $repository->findBy(['price' => ['$gt' => 100]]);

// Score less than 50
$results = $repository->findBy(['score' => ['$lt' => 50]]);

// Combine with exact match
$results = $repository->findBy(['name' => 'John', 'age' => ['$gte' => 18]]);
```

Supported operators: `$gte` (>=), `$gt` (>), `$lte` (<=), `$lt` (<).

> **Note:** Range queries work automatically with HASH format (NUMERIC index is auto-generated for int/float).
> For JSON format, you must explicitly declare a NUMERIC index: `#[Property(index: ['age' => 'NUMERIC'])]`.

## Pagination 📄

```php
$paginator = $repository->paginate(
    criteria: ['status' => 'active'],
    page: 2,
    itemsPerPage: 20,
    orderBy: ['createdAt' => 'DESC']
);

$paginator->getItems();        // Current page items
$paginator->getTotalItems();   // Total matching count
$paginator->getTotalPages();   // Total number of pages
$paginator->getCurrentPage();  // Current page number
$paginator->hasNextPage();     // bool
$paginator->hasPreviousPage(); // bool

// Iterable
foreach ($paginator as $item) {
    // ...
}
```

## Partial Updates (Merge) ⚡

Instead of re-persisting the entire object, use `merge()` to only update changed fields:

```php
$user = $objectManager->find(User::class, 1);
$user->name = 'New Name'; // Only this field changed

$objectManager->merge($user);  // Detects change, updates only 'name'
$objectManager->flush();
```

For new objects (not loaded via `find()`), `merge()` falls back to a full `persist()`.

## Batch Reads (Pipeline) 🚀

Load multiple objects by ID in a single Redis pipeline call:

```php
$users = $repository->findMultiple([1, 2, 3, 4, 5]);
```

## GEO Queries 🌍

Search objects within a geographic radius (requires a GEO-indexed property):

```php
#[RedisOm\Property(index: ['location' => 'GEO'])]
public string $location; // Format: "longitude,latitude"

$nearby = $repository->findByGeoRadius('location', 2.3522, 48.8566, 10, 'km');
```

## Advanced documentation 📚
- [Installation](https://github.com/clementtalleu/php-redis-om/blob/main/docs/installation.md)
- [Configuration](https://github.com/clementtalleu/php-redis-om/blob/main/docs/configuration.md)
- [Docker integration](https://github.com/clementtalleu/php-redis-om/blob/main/docs/docker_integration.md)
- [Mapping ](https://github.com/clementtalleu/php-redis-om/blob/main/docs/mapping.md)
- [Advanced usage ](https://github.com/clementtalleu/php-redis-om/blob/main/docs/advanced_usage.md)
- [Events ](https://github.com/clementtalleu/php-redis-om/blob/main/docs/events.md)
- [API Platform ](https://github.com/clementtalleu/php-redis-om/blob/main/docs/api_platform.md)
