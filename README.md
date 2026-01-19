
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
- Search and query capabilities
- Auto-expiration of your objects
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


## Advanced documentation 📚
- [Installation](https://github.com/clementtalleu/php-redis-om/blob/main/docs/installation.md)
- [Configuration](https://github.com/clementtalleu/php-redis-om/blob/main/docs/configuration.md)
- [Docker integration](https://github.com/clementtalleu/php-redis-om/blob/main/docs/docker_integration.md)
- [Mapping ](https://github.com/clementtalleu/php-redis-om/blob/main/docs/mapping.md)
- [Advanced usage ](https://github.com/clementtalleu/php-redis-om/blob/main/docs/advanced_usage.md)
- [Events ](https://github.com/clementtalleu/php-redis-om/blob/main/docs/events.md)
- [API Platform ](https://github.com/clementtalleu/php-redis-om/blob/main/docs/api_platform.md)
